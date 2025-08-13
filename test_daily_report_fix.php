<?php

require_once 'vendor/autoload.php';

use App\Models\Barang;
use App\Models\DailyReport;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;

// Test script to verify that new stock items appear in daily reports

echo "=== Testing Daily Report Fix ===\n\n";

// 1. Create a new test item
echo "1. Creating a new test item...\n";
$testBarang = Barang::create([
    'nama' => 'Test Beras Daily Report Fix',
    'kode' => 'TEST_DAILY_' . time(),
    'kategori' => 'Beras',
    'satuan' => 'kg',
    'stok' => 25,
    'harga_beli' => 8000,
    'harga_jual' => 10000,
    'deskripsi' => 'Test item for daily report fix',
    'created_by' => 1
]);

echo "✓ Created test item: {$testBarang->nama} (ID: {$testBarang->id})\n";
echo "✓ Initial stock: {$testBarang->stok}\n\n";

// 2. Check if stock movement was created
echo "2. Checking if stock movement was created...\n";
$stockMovement = StockMovement::where('barang_id', $testBarang->id)
    ->where('type', 'initial')
    ->first();

if ($stockMovement) {
    echo "✓ Stock movement created:\n";
    echo "  - Type: {$stockMovement->type}\n";
    echo "  - Quantity: {$stockMovement->quantity}\n";
    echo "  - User ID: {$stockMovement->user_id}\n";
    echo "  - Created at: {$stockMovement->created_at}\n\n";
} else {
    echo "✗ No stock movement found!\n\n";
}

// 3. Generate daily report for today
echo "3. Generating daily report for today...\n";
$user = User::first();
$today = Carbon::now();

try {
    $report = DailyReport::generateStockReport($today, $user->id);
    echo "✓ Daily report generated:\n";
    echo "  - Report ID: {$report->id}\n";
    echo "  - Date: {$report->report_date}\n";
    echo "  - Total movements: {$report->total_stock_movements}\n";
    echo "  - Total value: {$report->total_stock_value}\n\n";
} catch (Exception $e) {
    echo "✗ Failed to generate report: {$e->getMessage()}\n\n";
    exit(1);
}

// 4. Check if our test item appears in the report
echo "4. Checking if test item appears in the report...\n";
$reportData = $report->data;
$movements = collect($reportData['movements']);

$testItemInReport = $movements->filter(function($movement) use ($testBarang) {
    return $movement['barang_nama'] === $testBarang->nama;
});

if ($testItemInReport->count() > 0) {
    echo "✓ Test item found in daily report!\n";
    foreach ($testItemInReport as $item) {
        echo "  - Name: {$item['barang_nama']}\n";
        echo "  - Type: {$item['type']}\n";
        echo "  - Quantity: {$item['quantity']}\n";
        echo "  - Created at: {$item['created_at']}\n";
    }
    echo "\n";
} else {
    echo "✗ Test item NOT found in daily report!\n";
    echo "Total movements in report: {$movements->count()}\n\n";
}

// 5. Show all movements in today's report
echo "5. All movements in today's report:\n";
if ($movements->count() > 0) {
    foreach ($movements as $movement) {
        echo "  - {$movement['barang_nama']} ({$movement['type']}) - Qty: {$movement['quantity']}\n";
    }
} else {
    echo "  No movements found in report\n";
}

echo "\n=== Test Complete ===\n";

// Clean up
echo "\nCleaning up test data...\n";
if ($stockMovement) {
    $stockMovement->delete();
    echo "✓ Deleted stock movement\n";
}
$testBarang->delete();
echo "✓ Deleted test item\n";

echo "✓ Cleanup complete\n";
