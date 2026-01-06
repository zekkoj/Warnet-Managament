<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Order;

echo "=== CHECKING ORDER #20 ===\n\n";

$order20 = Order::find(20);

if ($order20) {
    echo "Order #20 Details:\n";
    echo "  ID: " . $order20->id . "\n";
    echo "  Total: Rp " . number_format($order20->total, 0, ',', '.') . "\n";
    echo "  Order Status: " . $order20->order_status . "\n";
    echo "  Payment Status: " . $order20->payment_status . "\n";
    echo "  Created At: " . $order20->created_at->format('Y-m-d H:i:s') . "\n";
    
    if ($order20->order_status === 'COMPLETED' && $order20->payment_status === 'PENDING') {
        echo "\n⚠️  Order #20 is COMPLETED but payment is still PENDING!\n";
        echo "Updating to PAID...\n";
        
        $order20->update(['payment_status' => 'PAID']);
        
        echo "✅ Updated Order #20 to PAID\n";
        
        // Check new revenue total
        $totalRevenue = Order::where('payment_status', 'PAID')->sum('total');
        echo "\n📊 New F&B Revenue: Rp " . number_format($totalRevenue, 0, ',', '.') . "\n";
    } else {
        echo "\n✅ Order #20 payment status is already correct: " . $order20->payment_status . "\n";
    }
} else {
    echo "❌ Order #20 not found in database\n";
}

echo "\n=== ALL ORDERS SUMMARY ===\n\n";

$allOrders = Order::orderBy('id', 'desc')->take(5)->get();

foreach ($allOrders as $order) {
    echo sprintf(
        "Order #%-3d | Rp %-10s | %-12s | %-12s\n",
        $order->id,
        number_format($order->total, 0, ',', '.'),
        $order->order_status,
        $order->payment_status
    );
}
