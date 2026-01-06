<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalSession;
use App\Models\PC;
use App\Services\PricingService;
use App\Events\SessionUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RentalSessionController extends Controller
{
    /**
     * Display active rental sessions
     */
    public function index()
    {
        // Self-healing: Ensure PC status consistency before fetching sessions
        $this->ensureDataConsistency();

        // Cache for 5 seconds to reduce database load from frequent polling
        $sessions = Cache::remember('active_sessions', 5, function () {
            // Optimized query: only load necessary columns and relations
            return RentalSession::with(['pc:id,pc_code,type', 'orders'])
                ->where('status', '!=', 'COMPLETED')
                ->select([
                    'id', 
                    'pc_id', 
                    'start_time', 
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
                ])
                ->orderBy('created_at', 'desc')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }


    /**
     * Create new rental session
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pc_id' => 'required|exists:pcs,id',
            'duration' => 'required|integer|min:1', // dalam menit
            'user_name' => 'nullable|string|max:255',
            'tier' => 'required|in:REGULER,VIP',
            'payment_method' => 'nullable|in:QRIS,CASH',
        ]);

        $pc = PC::findOrFail($validated['pc_id']);

        // Check if PC is already in use
        if ($pc->status === 'IN_USE') {
            return response()->json([
                'success' => false,
                'message' => 'PC is already in use',
            ], 409);
        }

        // Calculate pricing using PricingService
        $pricing = PricingService::calculatePrice(
            $validated['tier'],
            now(),
            $validated['duration']
        );

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
        $pc->update([
            'status' => 'IN_USE',
            'current_session_id' => $session->id
        ]);

        // Clear cache after creating new session
        Cache::forget('active_sessions');

        // Broadcast new session
        broadcast(new SessionUpdated($session));

        return response()->json([
            'success' => true,
            'message' => 'Rental session created',
            'data' => $session,
            'pricing_breakdown' => $pricing['breakdown'],
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
     */
    public function complete(Request $request, RentalSession $session)
    {
        // Make payment_method optional - use CASH as default for auto-complete
        $validated = $request->validate([
            'payment_method' => 'nullable|in:QRIS,CASH',
        ]);
        
        $paymentMethod = $validated['payment_method'] ?? 'CASH'; // Default to CASH for auto-complete

        // If session is paused, calculate the paused duration
        if ($session->status === 'PAUSED' && $session->paused_at) {
            $pausedDuration = now()->diffInMinutes($session->paused_at);
            $session->update([
                'paused_duration' => $session->paused_duration + $pausedDuration,
            ]);
        }

        // 1. Calculate Unpaid Orders
        $unpaidOrders = $session->orders()->where('payment_status', 'PENDING')->get();
        $ordersTotal = $unpaidOrders->sum('total');

        // 2. Mark orders as PAID
        if ($unpaidOrders->count() > 0) {
            $session->orders()->where('payment_status', 'PENDING')->update([
                'payment_status' => 'PAID',
                'payment_method' => $paymentMethod,
            ]);
        }

        // 3. Complete Session
        $session->update([
            'status' => 'COMPLETED',
            'end_time' => now(),
            // We do NOT add ordersTotal to total_cost here to keep rental cost separate in DB,
            // but we could add a 'grand_total' field if the schema supported it.
            // For now, we'll return the breakdown in the response.
        ]);

        // Update PC status back to IDLE and clear current_session_id
        $session->pc->update([
            'status' => 'IDLE',
            'current_session_id' => null
        ]);

        // Clear cache after completing session
        Cache::forget('active_sessions');

        // Broadcast session update
        broadcast(new SessionUpdated($session));

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
     */
    public function extend(Request $request, RentalSession $session)
    {
        $validated = $request->validate([
            'additional_duration' => 'required|integer|min:1', // dalam menit
        ]);

        // Recalculate total cost with new duration
        $newDuration = $session->duration + $validated['additional_duration'];
        $pricing = PricingService::calculatePrice(
            $session->tier,
            $session->start_time,
            $newDuration
        );

        $session->update([
            'duration' => $newDuration,
            'total_cost' => $pricing['total'],
        ]);

        // Clear cache after extending session
        Cache::forget('active_sessions');

        // Broadcast session update
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
     */
    public function pause(Request $request, RentalSession $session)
    {
        // Check if session is already paused
        if ($session->status === 'PAUSED') {
            return response()->json([
                'success' => false,
                'message' => 'Session is already paused',
            ], 400);
        }

        // Check if session is not active
        if ($session->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Only active sessions can be paused',
            ], 400);
        }

        // Validate remaining_seconds from frontend
        $validated = $request->validate([
            'remaining_seconds' => 'required|integer|min:0',
        ]);

        \Log::info("Pausing session {$session->id}", [
            'remaining_seconds_from_request' => $validated['remaining_seconds'],
            'current_status' => $session->status,
            'current_remaining_seconds' => $session->remaining_seconds,
        ]);

        // Update session status to PAUSED and record pause time + remaining seconds
        $session->update([
            'status' => 'PAUSED',
            'paused_at' => now(),
            'remaining_seconds' => $validated['remaining_seconds'], // Save exact remaining time
        ]);
        
        // Refresh model to get latest data from database
        $session->refresh();

        // Update PC status to show it's paused (still in use but paused)
        $session->pc->update([
            'status' => 'IN_USE',
        ]);

        // Clear cache after pausing session
        Cache::forget('active_sessions');

        // Broadcast session update (to all clients including the one that paused)
        broadcast(new SessionUpdated($session));

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
     */
    public function resume(Request $request, RentalSession $session)
    {
        // Check if session is paused
        if ($session->status !== 'PAUSED') {
            return response()->json([
                'success' => false,
                'message' => 'Only paused sessions can be resumed',
            ], 400);
        }

        // Check if paused_at is set
        if (!$session->paused_at) {
            return response()->json([
                'success' => false,
                'message' => 'Pause time not recorded',
            ], 400);
        }

        \Log::info("Resuming session {$session->id}", [
            'remaining_seconds_before' => $session->remaining_seconds,
            'paused_at' => $session->paused_at,
            'paused_duration_before' => $session->paused_duration,
            'start_time_before' => $session->start_time,
        ]);

        // Calculate paused duration and add to total paused_duration
        $pausedDuration = now()->diffInMinutes($session->paused_at);
        $totalPausedDuration = $session->paused_duration + $pausedDuration;

        // CRITICAL FIX: Adjust start_time to maintain accurate remaining time
        // Instead of relying on paused_duration (which has rounding errors),
        // we adjust start_time so that: now - start_time = duration - remaining_seconds
        // This ensures the frontend calculates the correct remaining time
        $remainingSeconds = $session->remaining_seconds;
        $durationSeconds = $session->duration * 60;
        $elapsedSeconds = $durationSeconds - $remainingSeconds;
        $newStartTime = now()->subSeconds($elapsedSeconds);

        // Update session status back to ACTIVE
        // IMPORTANT: Reset paused_duration to 0 because start_time is already adjusted
        // If we keep paused_duration, it will be subtracted twice (once in start_time, once in frontend calculation)
        $session->update([
            'status' => 'ACTIVE',
            'paused_at' => null,
            'paused_duration' => 0, // Reset to 0 since start_time is adjusted
            'remaining_seconds' => null, // Clear this so frontend calculates real-time
            'start_time' => $newStartTime, // Adjust start_time for accurate calculation
        ]);

        // Refresh model to get the latest data from database
        $session->refresh();

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
     */
    private function ensureDataConsistency()
    {
        // 1. Fix PCs marked IN_USE but have no current_session_id
        PC::where('status', 'IN_USE')
            ->whereNull('current_session_id')
            ->update(['status' => 'IDLE']);

        // 2. Fix PCs marked IN_USE where the session is COMPLETED or missing
        $pcs = PC::where('status', 'IN_USE')
            ->whereNotNull('current_session_id')
            ->with('currentSession')
            ->get();

        foreach ($pcs as $pc) {
            // If session relation is null (deleted) or status is COMPLETED
            if (!$pc->currentSession || $pc->currentSession->status === 'COMPLETED') {
                $pc->update([
                    'status' => 'IDLE',
                    'current_session_id' => null
                ]);
            }
        }
    }
}
