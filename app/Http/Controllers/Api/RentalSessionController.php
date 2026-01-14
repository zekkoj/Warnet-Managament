<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalSession;
use App\Models\PC;
use App\Services\PricingService;
use App\Events\SessionUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * RentalSessionController - Controller untuk mengelola sesi rental PC
 * 
 * Ini adalah CORE CONTROLLER untuk sistem rental warnet yang menangani:
 * - Membuat sesi rental baru dengan perhitungan harga otomatis
 * - Memperpanjang durasi sesi (extend)
 * - Pause dan resume sesi
 * - Menyelesaikan sesi dan menghitung total pembayaran (termasuk F&B)
 * - Self-healing mechanism untuk menjaga konsistensi data PC dan Session
 * 
 * FITUR KOMPLEKS:
 * - Perhitungan harga tier-based (jam 1 full price, jam 2+ diskon)
 * - Night rate discount (22:00-05:00)
 * - Pause/Resume dengan perhitungan waktu yang akurat
 * - Integrasi dengan F&B orders
 */
class RentalSessionController extends Controller
{
    /**
     * Display active rental sessions
     * 
     * Endpoint: GET /api/sessions
     * Menampilkan sesi rental (default: hanya yang aktif, bisa diubah dengan parameter)
     * 
     * OPTIMISASI PERFORMA:
     * - Cache 5 detik untuk mengurangi beban database dari frequent polling
     * - Select only necessary columns untuk mengurangi data transfer
     * - Self-healing mechanism sebelum fetch untuk menjaga konsistensi
     * 
     * Query Parameters:
     * - include_completed (boolean): Include completed sessions (default: false)
     * 
     * @return JsonResponse - daftar sesi dengan info:
     *         - PC yang digunakan
     *         - Waktu mulai dan durasi
     *         - Status (ACTIVE/PAUSED/COMPLETED)
     *         - Sisa waktu (remaining_seconds)
     *         - Orders yang terhubung
     */
    public function index(Request $request)
    {
        // Self-healing: Ensure PC status consistency before fetching sessions
        // Ini penting untuk memperbaiki data yang tidak konsisten
        // Contoh: PC status IN_USE tapi tidak ada session aktif
        $this->ensureDataConsistency();

        // Check if we should include completed sessions
        $includeCompleted = $request->query('include_completed', false);
        
        // Cache key berbeda tergantung parameter
        $cacheKey = $includeCompleted ? 'all_sessions' : 'active_sessions';

        // Cache for 5 seconds to reduce database load from frequent polling
        // Frontend biasanya polling setiap beberapa detik untuk update countdown timer
        $sessions = Cache::remember($cacheKey, 5, function () use ($includeCompleted) {
            // Optimized query: only load necessary columns and relations
            $query = RentalSession::with(['pc:id,pc_code,type', 'orders'])
                ->select([
                    'id', 
                    'pc_id', 
                    'start_time',
                    'end_time', 
                    'duration', 
                    'user_name', 
                    'tier', 
                    'status', 
                    'total_cost', 
                    'paused_at', 
                    'paused_duration',
                    'remaining_seconds', // Include remaining_seconds for accurate time tracking
                    'created_at',
                    'updated_at'
                ]);
            
            // Filter by status if not including completed
            if (!$includeCompleted) {
                $query->where('status', '!=', 'COMPLETED');
            }
            
            return $query->orderBy('created_at', 'desc')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }


    /**
     * Create new rental session
     * 
     * Endpoint: POST /api/sessions
     * Membuat sesi rental baru untuk customer
     * 
     * ALUR PROSES:
     * 1. Validasi input (PC ID, duration, tier)
     * 2. Check apakah PC sudah digunakan (conflict check)
     * 3. Hitung harga menggunakan PricingService (tier pricing + night discount)
     * 4. Buat session di database
     * 5. Update PC status menjadi IN_USE dan set current_session_id
     * 6. Clear cache dan broadcast update ke semua client
     * 
     * PERHITUNGAN HARGA:
     * - REGULER: Rp 7,000/jam (jam 1), Rp 6,000/jam (jam 2+)
     * - VIP: Rp 10,000/jam (jam 1), Rp 8,000/jam (jam 2+)
     * - Night discount: 20% OFF (22:00-05:00)
     * - Mixed period: otomatis split jika crossing night boundary
     * 
     * @param Request $request - body:
     *        - pc_id: ID PC yang akan dirent
     *        - duration: durasi dalam menit
     *        - user_name: nama customer (optional)
     *        - tier: REGULER atau VIP (menentukan harga)
     *        - payment_method: QRIS atau CASH (optional, default CASH)
     * @return JsonResponse - session yang baru dibuat + breakdown harga
     */
    public function store(Request $request)
    {
        // Validasi input request
        $validated = $request->validate([
            'pc_id' => 'required|exists:pcs,id',
            'duration' => 'required|integer|min:1', // dalam menit
            'user_name' => 'nullable|string|max:255',
            'tier' => 'required|in:REGULER,VIP',
            'payment_method' => 'nullable|in:QRIS,CASH',
        ]);

        // Ambil data PC dari database
        $pc = PC::findOrFail($validated['pc_id']);

        // Check if PC is already in use
        // Return 409 Conflict jika PC sedang digunakan
        if ($pc->status === 'IN_USE') {
            return response()->json([
                'success' => false,
                'message' => 'PC is already in use',
            ], 409);
        }

        // Calculate pricing using PricingService
        // PricingService menangani logika kompleks:
        // - Tier pricing (hour 1 vs hour 2+)
        // - Night discount
        // - Mixed period calculation
        $pricing = PricingService::calculatePrice(
            $validated['tier'],
            now(),
            $validated['duration']
        );

        // Buat session baru di database
        $session = RentalSession::create([
            'pc_id' => $validated['pc_id'],
            'start_time' => now(),
            'duration' => $validated['duration'],
            'user_name' => $validated['user_name'],
            'tier' => $validated['tier'],
            'status' => 'ACTIVE',
            'total_cost' => $pricing['total'],
            'payment_method' => $validated['payment_method'] ?? 'CASH',
        ]);

        // Update PC status AND set current_session_id
        // PENTING: Ini menjaga konsistensi data antara PC dan Session
        $pc->update([
            'status' => 'IN_USE',
            'current_session_id' => $session->id
        ]);

        // Clear cache after creating new session
        // Cache harus di-clear agar index() menampilkan data terbaru
        Cache::forget('active_sessions');

        // Broadcast new session
        // WebSocket akan push update ke semua client yang listening
        broadcast(new SessionUpdated($session));

        // Return session + breakdown harga untuk ditampilkan ke user
        return response()->json([
            'success' => true,
            'message' => 'Rental session created',
            'data' => $session,
            'pricing_breakdown' => $pricing['breakdown'], // Detail perhitungan per period
        ], 201);
    }

    /**
     * Display the specified session
     */
    public function show(RentalSession $session)
    {
        $session->load('pc', 'payment', 'orders');

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    /**
     * Update session
     */
    public function update(Request $request, RentalSession $session)
    {
        $validated = $request->validate([
            'status' => 'in:ACTIVE,PAUSED,COMPLETED',
            'user_name' => 'nullable|string|max:255',
        ]);

        $session->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Session updated',
            'data' => $session,
        ]);
    }

    /**
     * Remove the specified session
     */
    public function destroy(RentalSession $session)
    {
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session deleted',
        ]);
    }

    /**
     * Complete rental session
     * 
     * Endpoint: POST /api/sessions/{id}/complete
     * Method ini dipanggil saat customer selesai menggunakan PC
     * 
     * ALUR PROSES YANG KOMPLEKS:
     * 1. Jika session di-pause, hitung durasi pause terakhir
     * 2. Cari semua F&B orders yang belum dibayar (PENDING)
     * 3. Mark semua orders tersebut sebagai PAID
     * 4. Complete session dan set end_time
     * 5. Update PC status kembali ke IDLE
     * 6. Clear cache dan broadcast update
     * 
     * INTEGRASI F&B:
     * Session bisa punya multiple orders (makanan/minuman yang dipesan selama rental)
     * Saat complete, semua unpaid orders otomatis di-mark sebagai PAID
     * 
     * FINANCIAL CALCULATION:
     * - rental_cost: biaya rental PC (dari total_cost)
     * - orders_cost: total semua F&B orders
     * - grand_total: rental_cost + orders_cost
     * 
     * @param Request $request - body: { "payment_method": "QRIS|CASH" } (optional, default CASH)
     * @param RentalSession $session - session yang akan dicomplete
     * @return JsonResponse - session yang sudah complete + financial summary
     */
    public function complete(Request $request, RentalSession $session)
    {
        // Make payment_method optional - use CASH as default for auto-complete
        $validated = $request->validate([
            'payment_method' => 'nullable|in:QRIS,CASH',
        ]);
        
        $paymentMethod = $validated['payment_method'] ?? 'CASH'; // Default to CASH for auto-complete

        // If session is paused, calculate the paused duration
        // Hitung durasi pause terakhir dan tambahkan ke total paused_duration
        if ($session->status === 'PAUSED' && $session->paused_at) {
            $pausedDuration = now()->diffInMinutes($session->paused_at);
            $session->update([
                'paused_duration' => $session->paused_duration + $pausedDuration,
            ]);
        }

        // 1. Calculate Unpaid Orders
        // Ambil semua orders yang belum dibayar untuk session ini
        $unpaidOrders = $session->orders()->where('payment_status', 'PENDING')->get();
        $ordersTotal = $unpaidOrders->sum('total');

        // 2. Mark orders as PAID
        // Update payment_status dan payment_method untuk semua unpaid orders
        if ($unpaidOrders->count() > 0) {
            $session->orders()->where('payment_status', 'PENDING')->update([
                'payment_status' => 'PAID',
                'payment_method' => $paymentMethod,
            ]);
        }

        // 3. Complete Session
        // Set status COMPLETED dan end_time
        // Note: total_cost hanya berisi biaya rental, tidak termasuk F&B
        // (F&B cost dihitung terpisah di orders)
        $session->update([
            'status' => 'COMPLETED',
            'end_time' => now(),
            // We do NOT add ordersTotal to total_cost here to keep rental cost separate in DB,
            // but we could add a 'grand_total' field if the schema supported it.
            // For now, we'll return the breakdown in the response.
        ]);

        // Update PC status back to IDLE and clear current_session_id
        // PC siap digunakan customer berikutnya
        $session->pc->update([
            'status' => 'IDLE',
            'current_session_id' => null
        ]);

        // Clear cache after completing session
        Cache::forget('active_sessions');

        // Broadcast session update
        broadcast(new SessionUpdated($session));

        // Return dengan financial summary untuk kasir/customer
        return response()->json([
            'success' => true,
            'message' => 'Session completed',
            'data' => $session,
            'financial_summary' => [
                'rental_cost' => $session->total_cost,
                'orders_cost' => $ordersTotal,
                'grand_total' => $session->total_cost + $ordersTotal
            ]
        ]);
    }

    /**
     * Extend rental session
     * 
     * Endpoint: PATCH /api/sessions/{id}/extend
     * Customer bisa extend waktu rental jika mau lanjut main
     * 
     * PERHITUNGAN HARGA:
     * Recalculate total cost dengan durasi baru
     * PricingService akan hitung ulang dari awal dengan total duration baru
     * Contoh: Session 1 jam (Rp 7,000), extend 1 jam → total 2 jam
     * Perhitungan baru: jam 1 (Rp 7,000) + jam 2 (Rp 6,000) = Rp 13,000
     * 
     * CATATAN PENTING:
     * - Extend menggunakan start_time original, bukan waktu extend
     * - Night discount akan recalculated jika crossing time boundary
     * 
     * @param Request $request - body: { "additional_duration": <menit> }
     * @param RentalSession $session - session yang akan di-extend
     * @return JsonResponse - session terupdate + pricing breakdown baru
     */
    public function extend(Request $request, RentalSession $session)
    {
        // Validasi additional duration (dalam menit)
        $validated = $request->validate([
            'additional_duration' => 'required|integer|min:1', // dalam menit
        ]);

        // Recalculate total cost with new duration
        // Hitung durasi total baru (durasi lama + tambahan)
        $newDuration = $session->duration + $validated['additional_duration'];
        
        // Recalculate menggunakan PricingService dengan total durasi baru
        // start_time tetap sama (original start time)
        $pricing = PricingService::calculatePrice(
            $session->tier,
            $session->start_time,
            $newDuration
        );

        // Update session dengan durasi dan cost baru
        $session->update([
            'duration' => $newDuration,
            'total_cost' => $pricing['total'],
        ]);

        // Clear cache after extending session
        Cache::forget('active_sessions');

        // Broadcast session update
        // toOthers() agar client yang extend tidak menerima broadcast duplikat
        broadcast(new SessionUpdated($session))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Session extended',
            'data' => $session,
            'pricing_breakdown' => $pricing['breakdown'],
        ]);
    }

    /**
     * Pause rental session
     * 
     * Endpoint: POST /api/sessions/{id}/pause
     * Customer bisa pause session (misal mau istirahat/makan)
     * Waktu tidak berjalan saat pause, customer tidak dicharge
     * 
     * MEKANISME PAUSE:
     * 1. Validate session status (harus ACTIVE)
     * 2. Terima remaining_seconds dari frontend (countdown timer yang tepat)
     * 3. Save remaining_seconds ke database
     * 4. Set status PAUSED dan catat paused_at timestamp
     * 
     * MENGAPA REMAINING_SECONDS?
     * Frontend punya countdown timer yang akurat
     * Saat pause, kita save exact remaining seconds dari frontend
     * Ini mencegah loss of precision dari perhitungan ulang
     * 
     * @param Request $request - body: { "remaining_seconds": <detik> }
     * @param RentalSession $session - session yang akan di-pause
     * @return JsonResponse - session dengan status PAUSED
     */
    public function pause(Request $request, RentalSession $session)
    {
        // Check if session is already paused
        // Return 400 Bad Request jika sudah paused
        if ($session->status === 'PAUSED') {
            return response()->json([
                'success' => false,
                'message' => 'Session is already paused',
            ], 400);
        }

        // Check if session is not active
        // Hanya session ACTIVE yang bisa di-pause
        if ($session->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Only active sessions can be paused',
            ], 400);
        }

        // Validate remaining_seconds from frontend
        // Frontend mengirim sisa waktu yang exact dari countdown timer
        $validated = $request->validate([
            'remaining_seconds' => 'required|integer|min:0',
        ]);

        // Log untuk debugging/monitoring
        \Log::info("Pausing session {$session->id}", [
            'remaining_seconds_from_request' => $validated['remaining_seconds'],
            'current_status' => $session->status,
            'current_remaining_seconds' => $session->remaining_seconds,
        ]);

        // Update session status to PAUSED and record pause time + remaining seconds
        // KRUSIAL: Save remaining_seconds agar resume bisa restore waktu yang exact
        $session->update([
            'status' => 'PAUSED',
            'paused_at' => now(), // Timestamp kapan di-pause
            'remaining_seconds' => $validated['remaining_seconds'], // Save exact remaining time
        ]);
        
        // Refresh model to get latest data from database
        // Ensure model sync dengan database
        $session->refresh();

        // Update PC status to show it's paused (still in use but paused)
        // PC tetap IN_USE (tidak bisa digunakan orang lain)
        $session->pc->update([
            'status' => 'IN_USE',
        ]);

        // Clear cache after pausing session
        Cache::forget('active_sessions');

        // Broadcast session update (to all clients including the one that paused)
        // Semua client akan menerima update bahwa session ini paused
        broadcast(new SessionUpdated($session));

        // Log success
        \Log::info("Session {$session->id} paused successfully", [
            'remaining_seconds_after_update' => $session->remaining_seconds,
            'status' => $session->status,
            'paused_at' => $session->paused_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session paused successfully',
            'data' => $session,
        ]);
    }


    /**
     * Resume paused rental session
     * 
     * Endpoint: POST /api/sessions/{id}/resume
     * Resume session yang di-pause, waktu mulai berjalan lagi
     * 
     * MEKANISME RESUME - CRITICAL FIX:
     * 1. Hitung berapa lama session di-pause (paused duration)
     * 2. ADJUST start_time agar sisa waktu tetap akurat
     * 3. Reset paused_duration ke 0 (karena sudah adjust start_time)
     * 4. Clear remaining_seconds (biar frontend hitung real-time)
     * 
     * MENGAPA ADJUST START_TIME?
     * Masalah: Jika hanya menambah paused_duration, ada rounding error
     * Solusi: Adjust start_time sehingga: now - start_time = elapsed_time
     * 
     * Contoh:
     * - Session 60 menit, sudah jalan 20 menit, remaining 40 menit (2400 detik)
     * - Pause 10 menit
     * - Resume: start_time di-adjust mundur 20 menit dari now
     * - Frontend akan hitung: elapsed = now - start_time = 20 menit
     * - Sisa waktu = 60 - 20 = 40 menit (AKURAT!)
     * 
     * @param Request $request
     * @param RentalSession $session - session yang akan di-resume
     * @return JsonResponse - session dengan status ACTIVE
     */
    public function resume(Request $request, RentalSession $session)
    {
        // Check if session is paused
        // Hanya session PAUSED yang bisa di-resume
        if ($session->status !== 'PAUSED') {
            return response()->json([
                'success' => false,
                'message' => 'Only paused sessions can be resumed',
            ], 400);
        }

        // Check if paused_at is set
        // Validasi data integrity
        if (!$session->paused_at) {
            return response()->json([
                'success' => false,
                'message' => 'Pause time not recorded',
            ], 400);
        }

        // Log untuk debugging
        \Log::info("Resuming session {$session->id}", [
            'remaining_seconds_before' => $session->remaining_seconds,
            'paused_at' => $session->paused_at,
            'paused_duration_before' => $session->paused_duration,
            'start_time_before' => $session->start_time,
        ]);

        // Calculate paused duration and add to total paused_duration
        // Berapa lama session ini di-pause (dalam menit)
        $pausedDuration = now()->diffInMinutes($session->paused_at);
        $totalPausedDuration = $session->paused_duration + $pausedDuration;

        // CRITICAL FIX: Adjust start_time to maintain accurate remaining time
        // Instead of relying on paused_duration (which has rounding errors),
        // we adjust start_time so that: now - start_time = duration - remaining_seconds
        // This ensures the frontend calculates the correct remaining time
        
        // Ambil remaining_seconds yang disave saat pause
        $remainingSeconds = $session->remaining_seconds;
        
        // Total durasi dalam detik
        $durationSeconds = $session->duration * 60;
        
        // Elapsed time = total duration - remaining time
        $elapsedSeconds = $durationSeconds - $remainingSeconds;
        
        // Adjust start_time: mundurkan start_time sebesar elapsed_seconds dari now
        // Sehingga: now - newStartTime = elapsedSeconds
        $newStartTime = now()->subSeconds($elapsedSeconds);

        // Update session status back to ACTIVE
        // IMPORTANT: Reset paused_duration to 0 because start_time is already adjusted
        // If we keep paused_duration, it will be subtracted twice (once in start_time, once in frontend calculation)
        $session->update([
            'status' => 'ACTIVE',
            'paused_at' => null, // Clear paused_at
            'paused_duration' => 0, // Reset to 0 since start_time is adjusted
            'remaining_seconds' => null, // Clear this so frontend calculates real-time
            'start_time' => $newStartTime, // Adjust start_time for accurate calculation
        ]);

        // Refresh model to get the latest data from database
        $session->refresh();

        // Log success dengan info lengkap
        \Log::info("Session {$session->id} resumed successfully", [
            'remaining_seconds_after' => $session->remaining_seconds,
            'total_paused_duration' => $totalPausedDuration,
            'start_time_after' => $session->start_time,
            'adjusted_start_time' => $newStartTime,
        ]);

        // Clear cache after resuming session
        Cache::forget('active_sessions');

        // Broadcast session update (to all clients including the one that resumed)
        broadcast(new SessionUpdated($session));

        return response()->json([
            'success' => true,
            'message' => 'Session resumed successfully',
            'data' => $session,
        ]);
    }


    /**
     * Ensure consistency between PC status and Session status
     * Fixes issues where PCs are stuck in IN_USE but have no active session
     * 
     * SELF-HEALING MECHANISM:
     * Method ini otomatis dipanggil sebelum index() untuk fix data inconsistency
     * 
     * MASALAH YANG DI-FIX:
     * 1. PC status IN_USE tapi current_session_id = null
     *    → Set PC ke IDLE (PC tidak sedang digunakan)
     * 
     * 2. PC status IN_USE dengan current_session_id tapi:
     *    - Session tidak ada di database (deleted)
     *    - Session sudah COMPLETED
     *    → Set PC ke IDLE dan clear current_session_id
     * 
     * MENGAPA PERLU?
     * - Bug di aplikasi
     * - Manual database changes
     * - Crash saat update
     * - Network issues
     * 
     * Self-healing memastikan data selalu konsisten tanpa manual intervention
     */
    private function ensureDataConsistency()
    {
        // 1. Fix PCs marked IN_USE but have no current_session_id
        // PC tidak mungkin IN_USE jika tidak ada session
        PC::where('status', 'IN_USE')
            ->whereNull('current_session_id')
            ->update(['status' => 'IDLE']);

        // 2. Fix PCs marked IN_USE where the session is COMPLETED or missing
        // Ambil semua PC yang IN_USE dengan session_id
        $pcs = PC::where('status', 'IN_USE')
            ->whereNotNull('current_session_id')
            ->with('currentSession') // Eager load untuk check session
            ->get();

        // Check setiap PC
        foreach ($pcs as $pc) {
            // If session relation is null (deleted) or status is COMPLETED
            // Session null = data deleted/corrupted
            // Session COMPLETED = lupa update PC status
            if (!$pc->currentSession || $pc->currentSession->status === 'COMPLETED') {
                $pc->update([
                    'status' => 'IDLE',
                    'current_session_id' => null
                ]);
            }
        }
    }
}
