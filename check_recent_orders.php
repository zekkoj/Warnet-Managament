<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Order;

echo "=== RECENT ORDERS CHECK ===\n\n";

$recentOrders = Order::orderBy('id', 'desc')
    ->take(10)
    ->get(['id', 'total', 'order_status', 'payment_status', 'created_at']);

echo "Last 10 Orders:\n";
echo str_repeat("-", 100) . "\n";
printf("%-5s | %-12s | %-15s | %-15s | %s\n", "ID", "Total", "Order Status", "Payment Status", "Created At");
echo str_repeat("-", 100) . "\n";

foreach ($recentOrders as $order) {
    printf(
        "%-5s | Rp %-9s | %-15s | %-15s | %s\n",
        $order->id,
        number_format($order->total, 0, ',', '.'),
        $order->order_status,
        $order->payment_status,
        $order->created_at->format('Y-m-d H:i:s')
    );
}

echo "\n=== REVENUE SUMMARY ===\n\n";

$paidOrders = Order::where('payment_status', 'PAID')->get();
echo "Total PAID Orders: " . $paidOrders->count() . "\n";
echo "Total F&B Revenue: Rp " . number_format($paidOrders->sum('total'), 0, ',', '.') . "\n";

$completedButPending = Order::where('order_status', 'COMPLETED')
    ->where('payment_status', 'PENDING')
    ->get();

if ($completedButPending->count() > 0) {
    echo "\n⚠️  WARNING: Found " . $completedButPending->count() . " COMPLETED orders that are still PENDING payment:\n";
    foreach ($completedButPending as $order) {
        echo "  - Order #" . $order->id . " (Rp " . number_format($order->total, 0, ',', '.') . ")\n";
    }
}
