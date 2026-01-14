<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\RentalSession;

echo "=== CHECKING REVENUE DATA ===\n\n";

// Check Orders
$totalOrders = Order::count();
$completedOrders = Order::where('order_status', 'COMPLETED')->get();
$completedCount = $completedOrders->count();
$completedRevenue = $completedOrders->sum('total');

echo "ORDERS:\n";
echo "- Total Orders: {$totalOrders}\n";
echo "- Completed Orders: {$completedCount}\n";
echo "- Completed Revenue: Rp " . number_format($completedRevenue, 0) . "\n\n";

if ($completedCount > 0) {
    echo "Completed Orders Details:\n";
    foreach ($completedOrders as $order) {
        echo "  - Order #{$order->id}: Rp " . number_format($order->total, 0) 
             . " | Status: {$order->order_status} | Payment: {$order->payment_status}"
             . " | Created: {$order->created_at}\n";
    }
    echo "\n";
}

// Check Rental Sessions  
$totalSessions = RentalSession::count();
$completedSessions = RentalSession::where('status', 'COMPLETED')->get();
$completedSessionCount = $completedSessions->count();
$completedSessionRevenue = $completedSessions->sum('total_cost');

echo "RENTAL SESSIONS:\n";
echo "- Total Sessions: {$totalSessions}\n";
echo "- Completed Sessions: {$completedSessionCount}\n";
echo "- Completed Revenue: Rp " . number_format($completedSessionRevenue, 0) . "\n\n";

if ($completedSessionCount > 0) {
    echo "Completed Sessions Details:\n";
    foreach ($completedSessions as $session) {
        $pc = $session->pc;
        echo "  - Session #{$session->id} (PC: {$pc->pc_code}): Rp " . number_format($session->total_cost, 0)
             . " | Tier: {$session->tier} | Duration: {$session->duration}min"
             . " | Started: {$session->start_time}\n";
    }
    echo "\n";
}

echo "TOTAL REVENUE: Rp " . number_format($completedRevenue + $completedSessionRevenue, 0) . "\n";
