<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Http\Controllers\Api\AnalyticsController;
use Illuminate\Http\Request;

echo "=== TESTING REVENUE API ENDPOINT ===\n\n";

// Create mock request
$request = new Request([
    'start_date' => '2026-01-01',
    'end_date' => '2026-01-14'
]);

$controller = new AnalyticsController();
$response = $controller->revenue($request);
$data = json_decode($response->getContent(), true);

echo "API Response:\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($data['success']) {
    echo "Summary:\n";
    echo "- Total Revenue: Rp " . number_format($data['summary']['total_revenue'] ?? 0, 0) . "\n";
    echo "- PC Rental Revenue: Rp " . number_format($data['summary']['pc_rental_revenue'] ?? 0, 0) . "\n";
    echo "- F&B Revenue: Rp " . number_format($data['summary']['f&b_revenue'] ?? 0, 0) . "\n\n";
    
    echo "PC Revenue Data Count: " . count($data['data']['pc_revenue'] ?? []) . "\n";
    echo "F&B Revenue Data Count: " . count($data['data']['f&b_revenue'] ?? []) . "\n";
} else {
    echo "ERROR: " . ($data['message'] ?? 'Unknown error') . "\n";
}
