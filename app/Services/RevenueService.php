<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RentalSession;
use Illuminate\Support\Facades\DB;

class RevenueService
{
    /**
     * Get completed orders with revenue calculation
     * This is the single source of truth for orders that should be counted in revenue
     * Revenue is based on order_status = 'COMPLETED' (order delivered to customer)
     */
    public static function getPaidOrders($startDate = null, $endDate = null)
    {
        // Use order_status = 'COMPLETED' for revenue calculation
        // Completed orders = revenue, regardless of payment status
        $query = Order::where('order_status', 'COMPLETED');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->with('orderItems.menu', 'payment');
    }

    /**
     * Get F&B revenue with breakdown by date and hour
     * Used by Analytics Controller for revenue reporting
     */
    public static function getFbRevenueByDateAndHour($startDate, $endDate)
    {
        $driver = DB::getDriverName();
        
        $baseQuery = self::getPaidOrders($startDate, $endDate);

        if ($driver === 'sqlite') {
            return $baseQuery
                ->selectRaw("
                    DATE(created_at) as date,
                    CAST(strftime('%H', created_at) as INTEGER) as hour,
                    'F&B' as category,
                    SUM(total) as amount,
                    COUNT(*) as count
                ")
                ->groupBy('date', 'hour')
                ->orderBy('date')
                ->get();
        } else {
            return $baseQuery
                ->selectRaw("
                    DATE(created_at) as date,
                    HOUR(created_at) as hour,
                    'F&B' as category,
                    SUM(total) as amount,
                    COUNT(*) as count
                ")
                ->groupBy('date', 'hour')
                ->orderBy('date')
                ->get();
        }
    }

    /**
     * Get total F&B revenue for given period
     */
    public static function getTotalFbRevenue($startDate, $endDate)
    {
        return self::getPaidOrders($startDate, $endDate)
            ->sum('total');
    }

    /**
     * Get total F&B orders count for given period
     */
    public static function getTotalFbOrderCount($startDate, $endDate)
    {
        return self::getPaidOrders($startDate, $endDate)
            ->count();
    }

    /**
     * Get F&B revenue by category (for analytics breakdown)
     */
    public static function getFbRevenueByCategory($startDate, $endDate)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menus', 'order_items.menu_id', '=', 'menus.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status', 'COMPLETED')
            ->selectRaw('
                menus.category,
                COUNT(*) as item_count,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.subtotal) as total_revenue
            ')
            ->groupBy('menus.category')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Get top selling F&B items
     */
    public static function getTopFbItems($startDate, $endDate, $limit = 10)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menus', 'order_items.menu_id', '=', 'menus.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status', 'COMPLETED')
            ->selectRaw('
                menus.id,
                menus.name,
                menus.category,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.subtotal) as total_revenue
            ')
            ->groupBy('menus.id', 'menus.name', 'menus.category')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Get average order value
     */
    public static function getAverageOrderValue($startDate, $endDate)
    {
        $totalOrders = self::getTotalFbOrderCount($startDate, $endDate);
        $totalRevenue = self::getTotalFbRevenue($startDate, $endDate);

        return $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
    }

    /**
     * Get detailed order analytics summary
     */
    public static function getOrderAnalyticsSummary($startDate, $endDate)
    {
        $totalOrders = self::getTotalFbOrderCount($startDate, $endDate);
        $totalRevenue = self::getTotalFbRevenue($startDate, $endDate);
        $avgOrderValue = self::getAverageOrderValue($startDate, $endDate);

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
        ];
    }
}
