<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PC;
use App\Events\PcStatusChanged;
use Illuminate\Http\Request;

class PCController extends Controller
{
    /**
     * Display a listing of all PCs
     */
    public function index(Request $request)
    {
        $query = PC::with('currentSession');
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }
        
        $pcs = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $pcs,
        ]);
    }

    /**
     * Store a newly created PC
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pc_code' => 'required|string|unique:pcs',
            'location' => 'required|string',
            'position' => 'required|string',
            'type' => 'required|in:REGULER,VIP',
        ]);

        $pc = PC::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'PC created successfully',
            'data' => $pc,
        ], 201);
    }

    /**
     * Display the specified PC
     */
    public function show(PC $pc)
    {
        $pc->load('currentSession', 'rentalSessions');
        
        return response()->json([
            'success' => true,
            'data' => $pc,
        ]);
    }

    /**
     * Update the specified PC
     */
    public function update(Request $request, PC $pc)
    {
        $validated = $request->validate([
            'location' => 'string',
            'position' => 'string',
            'type' => 'in:REGULER,VIP',
            'status' => 'in:ONLINE,OFFLINE,IDLE,IN_USE,MAINTENANCE',
            'cpu_usage' => 'integer|min:0|max:100',
            'ram_usage' => 'integer|min:0|max:100',
            'disk_usage' => 'integer|min:0|max:100',
            'current_process' => 'array',
        ]);

        // If changing to MAINTENANCE or IDLE, also clear current_session_id
        if (in_array($validated['status'] ?? null, ['MAINTENANCE', 'IDLE'])) {
            $validated['current_session_id'] = null;
        }

        $pc->update($validated);

        // Broadcast PC status change
        broadcast(new PcStatusChanged($pc));

        return response()->json([
            'success' => true,
            'message' => 'PC updated successfully',
            'data' => $pc,
        ]);
    }

    /**
     * Remove the specified PC
     */
    public function destroy(PC $pc)
    {
        $pc->delete();

        return response()->json([
            'success' => true,
            'message' => 'PC deleted successfully',
        ]);
    }

    /**
     * Send control command to PC (shutdown, restart, lock)
     */
    public function control(Request $request, PC $pc)
    {
        $validated = $request->validate([
            'command' => 'required|in:shutdown,restart,lock,unlock',
        ]);

        // TODO: Implement actual PC control via agent/service
        // This would integrate with PC agent running on each PC

        return response()->json([
            'success' => true,
            'message' => 'Command sent to PC: ' . $validated['command'],
            'pc_id' => $pc->id,
            'command' => $validated['command'],
        ]);
    }

    /**
     * Get PC usage history
     */
    public function history(PC $pc)
    {
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
