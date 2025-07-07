<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Barang;
use App\Models\User;

echo "=== CONTROLLER STOCK TEST ===\n";

// Login as kasir
$kasir = User::where('username', 'kasir')->first();
if (!$kasir) {
    echo "Kasir user not found!\n";
    exit;
}

auth()->login($kasir);
echo "Logged in as: {$kasir->name}\n";

// Get first barang
$barang = Barang::first();
if (!$barang) {
    echo "No barang found!\n";
    exit;
}

echo "Testing with: {$barang->nama}\n";
echo "Initial stock: {$barang->stok}\n";

// Simulate what happens in PenjualanController
echo "\n--- Simulating PenjualanController logic ---\n";

$item = [
    'barang_id' => $barang->id,
    'jumlah' => 3,
    'harga_satuan' => $barang->harga_jual,
];

echo "Item quantity to reduce: {$item['jumlah']}\n";

$initialStock = $barang->stok;

// This is what happens in PenjualanController store method
echo "Calling recordStockMovement('out', {$item['jumlah']}, 'Test transaction')...\n";
$barang->recordStockMovement(
    'out',
    $item['jumlah'], // Positive quantity
    'Test transaction',
    auth()->id()
);

$barang->refresh();
echo "Stock after recordStockMovement: {$barang->stok}\n";
echo "Expected: " . ($initialStock - $item['jumlah']) . "\n";
echo "Actual difference: " . ($initialStock - $barang->stok) . "\n";

if (($initialStock - $barang->stok) == $item['jumlah']) {
    echo "✅ Controller logic works correctly!\n";
} else {
    echo "❌ Controller logic has issues!\n";
}

// Check stock movement record
$lastMovement = $barang->stockMovements()->latest()->first();
if ($lastMovement) {
    echo "\nLast stock movement:\n";
    echo "- Type: {$lastMovement->type}\n";
    echo "- Quantity: {$lastMovement->quantity}\n";
    echo "- Stock before: {$lastMovement->stock_before}\n";
    echo "- Stock after: {$lastMovement->stock_after}\n";
    echo "- User: {$lastMovement->user->name}\n";
} else {
    echo "\n❌ No stock movement record found!\n";
}

echo "\n=== TEST COMPLETED ===\n";
