<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Order;
use App\Models\RentalSession;
use Carbon\Carbon;

echo "=== TESTING ANALYTICS FILTERS ===\n\n";

$today = Carbon::today();
$endDate = Carbon::now();

echo "Filter Period: {$today->format('Y-m-d')} to {$endDate->format('Y-m-d H:i:s')}\n\n";

// Check Sessions with filter
echo "RENTAL SESSIONS (with filter):\n";
$sessions = RentalSession::whereBetween('start_time', [$today, $endDate])->get();
echo "- Count: " . $sessions->count() . "\n";
foreach ($sessions as $s) {
    echo "  - Session #{$s->id}: {$s->status} | Start: {$s->start_time} | Cost: Rp " . number_format($s->total_cost ?? 0, 0) . "\n";
}
echo "\n";

// Check Orders with filter
echo "ORDERS (with filter on created_at):\n";
$orders = Order::whereBetween('created_at', [$today, $endDate])->get();
echo "- Count: " . $orders->count() . "\n";
foreach ($orders as $o) {
    echo "  - Order #{$o->id}: {$o->order_status} | Created: {$o->created_at} | Total: Rp " . number_format($o->total, 0) . "\n";
}
echo "\n";

// Test what RevenueService returns
echo "=== REVENUE SERVICE OUTPUT ===\n";
$fbRevenue = \App\Services\RevenueService::getTotalFbRevenue($today, $endDate);
echo "F&B Revenue: Rp " . number_format($fbRevenue, 0) . "\n";

$pcRevenue = RentalSession::whereBetween('start_time', [$today, $endDate])->sum('total_cost');
echo "PC Rental Revenue: Rp " . number_format($pcRevenue, 0) . "\n";

echo "TOTAL: Rp " . number_format($fbRevenue + $pcRevenue, 0) . "\n";
