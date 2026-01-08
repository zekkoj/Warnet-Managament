<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PC;
use App\Events\PcStatusChanged;
use Illuminate\Http\Request;

/**
 * PCController - Controller untuk mengelola PC warnet
 * 
 * Controller ini bertanggung jawab untuk:
 * - Menampilkan daftar PC beserta status real-time
 * - Membuat, mengupdate, dan menghapus data PC
 * - Mengirim perintah kontrol ke PC (shutdown, restart, lock)
 * - Melihat history penggunaan PC
 */
class PCController extends Controller
{
    /**
     * Display a listing of all PCs
     * 
     * Endpoint: GET /api/pcs
     * Method ini mengambil semua data PC beserta sesi rental yang sedang aktif
     * 
     * @param Request $request - dapat menerima query parameter 'status' untuk filter
     * @return JsonResponse - daftar PC dengan informasi: 
     *         - pc_code (kode PC seperti "PC-01" atau "VIP-01")
     *         - status (ONLINE/OFFLINE/IDLE/IN_USE/MAINTENANCE)
     *         - currentSession (sesi rental yang sedang berjalan, jika ada)
     *         - cpu_usage, ram_usage, disk_usage (monitoring resource)
     */
    public function index(Request $request)
    {
        // Buat query dengan eager loading currentSession untuk menghindari N+1 problem
        $query = PC::with('currentSession');
        
        // Filter by status if provided
        // Contoh: /api/pcs?status=IN_USE akan menampilkan hanya PC yang sedang digunakan
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }
        
        // Execute query dan ambil semua PC
        $pcs = $query->get();
        
        // Return response dalam format JSON standar
        return response()->json([
            'success' => true,
            'data' => $pcs,
        ]);
    }

    /**
     * Store a newly created PC
     * 
     * Endpoint: POST /api/pc
     * Method ini digunakan untuk menambahkan PC baru ke dalam sistem
     * Biasanya digunakan saat setup awal atau menambah PC baru ke warnet
     * 
     * @param Request $request - body harus berisi:
     *        - pc_code: kode unik PC (contoh: "PC-01", "VIP-15")
     *        - location: lokasi fisik (contoh: "Lantai 1", "Lantai 2")
     *        - position: posisi detail (contoh: "Row A-1", "Booth 5")
     *        - type: tipe PC (REGULER atau VIP) - menentukan harga rental
     * @return JsonResponse - PC yang baru dibuat dengan status 201 Created
     */
    public function store(Request $request)
    {
        // Validasi input - pc_code harus unik di database
        $validated = $request->validate([
            'pc_code' => 'required|string|unique:pcs', // unique mencegah duplikasi kode PC
            'location' => 'required|string',
            'position' => 'required|string',
            'type' => 'required|in:REGULER,VIP', // hanya menerima REGULER atau VIP
        ]);

        // Buat PC baru di database dengan status default OFFLINE (lihat migration)
        $pc = PC::create($validated);

        // Return response dengan status 201 (Created)
        return response()->json([
            'success' => true,
            'message' => 'PC created successfully',
            'data' => $pc,
        ], 201);
    }

    /**
     * Display the specified PC
     * 
     * Endpoint: GET /api/pcs/{id}
     * Menampilkan detail lengkap satu PC tertentu
     * Berguna untuk monitoring detail PC atau troubleshooting
     * 
     * @param PC $pc - Laravel route model binding otomatis inject model PC berdasarkan ID
     * @return JsonResponse - detail PC lengkap dengan:
     *         - currentSession: sesi yang sedang berjalan (jika ada)
     *         - rentalSessions: history semua sesi rental PC ini
     */
    public function show(PC $pc)
    {
        // Load relationships untuk mendapatkan data sesi aktif dan history
        $pc->load('currentSession', 'rentalSessions');
        
        return response()->json([
            'success' => true,
            'data' => $pc,
        ]);
    }

    /**
     * Update the specified PC
     * 
     * Endpoint: PUT/PATCH /api/pc/{id}
     * Method ini digunakan untuk:
     * - Update informasi PC (lokasi, posisi, tipe)
     * - Update status PC (ONLINE, OFFLINE, IDLE, IN_USE, MAINTENANCE)
     * - Update monitoring resource (CPU, RAM, Disk usage)
     * - Biasanya dipanggil oleh PC agent yang berjalan di setiap PC untuk update status real-time
     * 
     * @param Request $request - field yang bisa diupdate (semua optional):
     *        - location, position, type: info PC
     *        - status: status operasional PC
     *        - cpu_usage, ram_usage, disk_usage: persentase penggunaan (0-100)
     *        - current_process: array proses yang sedang berjalan
     * @param PC $pc - PC yang akan diupdate
     * @return JsonResponse - PC yang sudah terupdate
     */
    public function update(Request $request, PC $pc)
    {
        // Validasi - semua field optional karena bisa partial update
        $validated = $request->validate([
            'location' => 'string',
            'position' => 'string',
            'type' => 'in:REGULER,VIP',
            'status' => 'in:ONLINE,OFFLINE,IDLE,IN_USE,MAINTENANCE',
            'cpu_usage' => 'integer|min:0|max:100', // persentase 0-100
            'ram_usage' => 'integer|min:0|max:100',
            'disk_usage' => 'integer|min:0|max:100',
            'current_process' => 'array', // JSON array proses yang berjalan
        ]);

        // PENTING: Saat PC masuk MAINTENANCE atau IDLE, clear current_session_id
        // Ini menjaga konsistensi data - PC tidak bisa punya sesi aktif jika MAINTENANCE/IDLE
        if (in_array($validated['status'] ?? null, ['MAINTENANCE', 'IDLE'])) {
            $validated['current_session_id'] = null;
        }

        // Update PC di database
        $pc->update($validated);

        // Broadcast perubahan status ke semua client melalui WebSocket (Laravel Reverb)
        // Ini memungkinkan monitoring dashboard update real-time tanpa refresh
        broadcast(new PcStatusChanged($pc));

        return response()->json([
            'success' => true,
            'message' => 'PC updated successfully',
            'data' => $pc,
        ]);
    }

    /**
     * Remove the specified PC
     * 
     * Endpoint: DELETE /api/pc/{id}
     * Menghapus PC dari sistem
     * HATI-HATI: Karena ada foreign key constraint dengan cascade,
     * menghapus PC akan otomatis menghapus semua rental sessions terkait
     * 
     * @param PC $pc - PC yang akan dihapus
     * @return JsonResponse - konfirmasi penghapusan
     */
    public function destroy(PC $pc)
    {
        // Soft delete atau hard delete tergantung setting model
        // Dengan cascade, semua rental_sessions juga akan terhapus
        $pc->delete();

        return response()->json([
            'success' => true,
            'message' => 'PC deleted successfully',
        ]);
    }

    /**
     * Send control command to PC (shutdown, restart, lock)
     * 
     * Endpoint: POST /api/pc/{id}/control
     * Mengirim perintah kontrol ke PC untuk:
     * - shutdown: mematikan PC
     * - restart: restart PC
     * - lock: kunci layar PC
     * - unlock: buka kunci layar
     * 
     * IMPLEMENTASI: Memerlukan PC agent yang running di setiap PC
     * Agent akan listen ke perintah ini dan eksekusi di level OS
     * 
     * @param Request $request - body: { "command": "shutdown|restart|lock|unlock" }
     * @param PC $pc - PC target
     * @return JsonResponse - konfirmasi command dikirim
     */
    public function control(Request $request, PC $pc)
    {
        // Validasi command yang diizinkan
        $validated = $request->validate([
            'command' => 'required|in:shutdown,restart,lock,unlock',
        ]);

        // TODO: Implement actual PC control via agent/service
        // This would integrate with PC agent running on each PC
        // Implementasi bisa menggunakan:
        // - WebSocket untuk komunikasi real-time
        // - Queue system untuk reliable delivery
        // - HTTP callback ke agent endpoint di PC

        return response()->json([
            'success' => true,
            'message' => 'Command sent to PC: ' . $validated['command'],
            'pc_id' => $pc->id,
            'command' => $validated['command'],
        ]);
    }

    /**
     * Get PC usage history
     * 
     * Endpoint: GET /api/pc/{id}/history
     * Mengambil riwayat penggunaan PC (rental sessions)
     * Berguna untuk:
     * - Analisis penggunaan per PC
     * - Troubleshooting masalah PC tertentu
     * - Laporan usage per PC
     * 
     * @param PC $pc - PC yang ingin dilihat historynya
     * @return JsonResponse - 50 rental sessions terakhir, sorted by start_time descending
     */
    public function history(PC $pc)
    {
        // Ambil 50 sesi terakhir, urut dari yang terbaru
        $history = $pc->rentalSessions()
            ->orderBy('start_time', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
