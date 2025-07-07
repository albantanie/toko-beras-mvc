<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Barang;

echo "=== STOCK MANAGEMENT TEST ===\n";

// Get first barang
$barang = Barang::first();
if (!$barang) {
    echo "No barang found!\n";
    exit;
}

echo "Testing with: {$barang->nama}\n";
echo "Initial stock: {$barang->stok}\n";

// Test recordStockMovement
echo "\n--- Testing recordStockMovement ---\n";
$initialStock = $barang->stok;

echo "Calling recordStockMovement('out', 5, 'Test reduction')...\n";
$barang->recordStockMovement('out', 5, 'Test reduction');

$barang->refresh();
echo "Stock after recordStockMovement: {$barang->stok}\n";
echo "Expected: " . ($initialStock - 5) . "\n";
echo "Difference: " . ($initialStock - $barang->stok) . "\n";

if (($initialStock - $barang->stok) == 5) {
    echo "✅ recordStockMovement works correctly!\n";
} else {
    echo "❌ recordStockMovement has issues!\n";
}

// Check stock movement record
$lastMovement = $barang->stockMovements()->latest()->first();
if ($lastMovement) {
    echo "\nLast stock movement:\n";
    echo "- Type: {$lastMovement->type}\n";
    echo "- Quantity: {$lastMovement->quantity}\n";
    echo "- Stock before: {$lastMovement->stock_before}\n";
    echo "- Stock after: {$lastMovement->stock_after}\n";
} else {
    echo "\n❌ No stock movement record found!\n";
}

echo "\n=== TEST COMPLETED ===\n";
