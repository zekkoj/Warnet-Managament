<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\RentalSession;

echo "=== CHECKING SESSIONS DATA ===\n\n";

$allSessions = RentalSession::all();
echo "Total Sessions: " . $allSessions->count() . "\n\n";

foreach ($allSessions as $session) {
    $pc = $session->pc;
    echo "Session #{$session->id}:\n";
    echo "  PC: {$pc->pc_code}\n";
    echo "  Status: {$session->status}\n";
    echo "  Duration: {$session->duration} min\n";
    echo "  Total Cost: Rp " . number_format($session->total_cost ?? 0, 0) . "\n";
    echo "  Start: {$session->start_time}\n";
    if ($session->end_time) {
        echo "  End: {$session->end_time}\n";
    }
    echo "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Active: " . RentalSession::where('status', 'ACTIVE')->count() . "\n";
echo "Completed: " . RentalSession::where('status', 'COMPLETED')->count() . "\n";
echo "Total Revenue (Completed): Rp " . number_format(
    RentalSession::where('status', 'COMPLETED')->sum('total_cost'), 0
) . "\n";
