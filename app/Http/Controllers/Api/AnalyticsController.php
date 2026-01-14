<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RevenueLog;
use App\Models\RentalSession;
use App\Models\Order;
use App\Services\RevenueService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get revenue analytics
     * Uses RevenueService for F&B revenue to ensure sync with Orders Management
     */
    public function revenue(Request $request)
    {
        $period = $request->query('period', 'daily'); // daily, weekly, monthly
        $startDate = $request->query('start_date', now()->startOfMonth());
        // CRITICAL: Always parse end_date and apply endOfDay to include all data from that day
        $endDate = $request->has('end_date') 
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        $driver = DB::getDriverName();

        // Get PC rental revenue with database-agnostic date functions
        if ($driver === 'sqlite') {
            $pcRevenue = RentalSession::whereBetween('start_time', [$startDate, $endDate])
                ->selectRaw("
                    DATE(start_time) as date,
                    CAST(strftime('%H', start_time) as INTEGER) as hour,
                    tier as category,
                    SUM(total_cost) as amount,
                    COUNT(*) as count
                ")
                ->groupBy('date', 'hour', 'tier')
                ->orderBy('date')
                ->get();
        } else {
            $pcRevenue = RentalSession::whereBetween('start_time', [$startDate, $endDate])
                ->selectRaw("
                    DATE(start_time) as date,
                    HOUR(start_time) as hour,
                    tier as category,
                    SUM(total_cost) as amount,
                    COUNT(*) as count
                ")
                ->groupBy('date', 'hour', 'tier')
                ->orderBy('date')
                ->get();
        }

        // Get F&B revenue using RevenueService (single source of truth)
        $fbRevenue = RevenueService::getFbRevenueByDateAndHour($startDate, $endDate);

        // Combine revenues
        $totalPCRevenue = $pcRevenue->sum('amount');
        $totalFBRevenue = $fbRevenue->sum('amount');
        $totalRevenue = $totalPCRevenue + $totalFBRevenue;

        return response()->json([
            'success' => true,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'pc_rental_revenue' => $totalPCRevenue,
                'f&b_revenue' => $totalFBRevenue,
                'period' => $period,
            ],
            'data' => [
                'pc_revenue' => $pcRevenue,
                'f&b_revenue' => $fbRevenue,
            ],
        ]);
    }

    /**
     * Get PC usage analytics
     */
    public function pcUsage(Request $request)
    {
        $startDate = $request->query('start_date', now()->startOfMonth());
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        $driver = DB::getDriverName();

        // Most used PCs
        $mostUsedPCs = RentalSession::whereBetween('start_time', [$startDate, $endDate])
            ->selectRaw('
                pc_id,
                COUNT(*) as usage_count,
                SUM(duration) as total_minutes,
                AVG(duration) as avg_duration
            ')
            ->groupBy('pc_id')
            ->with('pc')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // Peak hours with database-agnostic date functions
        if ($driver === 'sqlite') {
            $peakHours = RentalSession::whereBetween('start_time', [$startDate, $endDate])
                ->selectRaw('
                    CAST(strftime(\'%H\', start_time) as INTEGER) as hour,
                    COUNT(*) as count,
                    AVG(duration) as avg_duration
                ')
                ->groupBy('hour')
                ->orderByDesc('count')
                ->get();
        } else {
            $peakHours = RentalSession::whereBetween('start_time', [$startDate, $endDate])
                ->selectRaw('
                    HOUR(start_time) as hour,
                    COUNT(*) as count,
                    AVG(duration) as avg_duration
                ')
                ->groupBy('hour')
                ->orderByDesc('count')
                ->get();
        }

        // Occupancy rate
        $totalSessions = RentalSession::whereBetween('start_time', [$startDate, $endDate])->count();
        $completedSessions = RentalSession::whereBetween('start_time', [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'completion_rate' => $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0,
            ],
            'data' => [
                'most_used_pcs' => $mostUsedPCs,
                'peak_hours' => $peakHours,
            ],
        ]);
    }

    /**
     * Get F&B analytics
     * Uses RevenueService for consistent revenue filtering with Orders Management
     */
    public function fAndB(Request $request)
    {
        $startDate = $request->query('start_date', now()->startOfMonth());
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        // Get all F&B analytics using RevenueService (single source of truth)
        $topItems = RevenueService::getTopFbItems($startDate, $endDate, 10);
        $categoryRevenue = RevenueService::getFbRevenueByCategory($startDate, $endDate);
        $analyticsSummary = RevenueService::getOrderAnalyticsSummary($startDate, $endDate);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_orders' => $analyticsSummary['total_orders'],
                'total_revenue' => $analyticsSummary['total_revenue'],
                'avg_order_value' => $analyticsSummary['avg_order_value'],
            ],
            'data' => [
                'top_items' => $topItems,
                'category_breakdown' => $categoryRevenue,
            ],
        ]);
    }

    /**
     * Export analytics report
     */
    public function export(Request $request)
    {
        // TODO: Implement PDF/Excel export
        return response()->json([
            'success' => false,
            'message' => 'Export functionality coming soon',
        ]);
    }
}
