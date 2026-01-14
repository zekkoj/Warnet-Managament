<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Order;
use App\Models\RentalSession;
use Carbon\Carbon;

echo "=== SIMULATING FRONTEND REQUEST ===\n\n";

// Simulasi request dari frontend
$startDate = '2026-01-14'; // String dari frontend
$endDate = '2026-01-14';   // String dari frontend

// Backend default (SEBELUM FIX)
echo "BEFORE FIX (treating as exact timestamp):\n";
$beforeSessions = RentalSession::whereBetween('start_time', [$startDate, $endDate])->count();
echo "- Sessions found: $beforeSessions\n\n";

// Backend SETELAH FIX (using endOfDay)
echo "AFTER FIX (using endOfDay):\n";
$endDateFixed = Carbon::parse($endDate)->endOfDay();
$afterSessions = RentalSession::whereBetween('start_time', [$startDate, $endDateFixed])->count();
$afterRevenue = RentalSession::whereBetween('start_time', [$startDate, $endDateFixed])->sum('total_cost');
echo "- Sessions found: $afterSessions\n";
echo "- PC Revenue: Rp " . number_format($afterRevenue, 0) . "\n\n";

$afterOrders = Order::whereBetween('created_at', [$startDate, $endDateFixed])
    ->where('order_status', 'COMPLETED')
    ->count();
$afterFbRevenue = Order::whereBetween('created_at', [$startDate, $endDateFixed])
    ->where('order_status', 'COMPLETED')
    ->sum('total');
echo "- Orders found: $afterOrders\n";
echo "- F&B Revenue: Rp " . number_format($afterFbRevenue, 0) . "\n";
echo "- TOTAL REVENUE: Rp " . number_format($afterRevenue + $afterFbRevenue, 0) . "\n";
