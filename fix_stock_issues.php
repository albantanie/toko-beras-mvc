<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Barang;
use App\Models\StockMovement;
use App\Models\StockValuation;

// Load Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Fixing Stock Issues...\n\n";

// 1. Fix negative stock values
echo "1. Fixing negative stock values...\n";
$negativeStockItems = Barang::where('stok', '<', 0)->get();

foreach ($negativeStockItems as $barang) {
    echo "   - {$barang->nama}: {$barang->stok} -> 0\n";
    $barang->update(['stok' => 0]);
    
    // Record correction movement
    $barang->recordStockMovement(
        type: 'correction',
        quantity: abs($barang->stok),
        description: 'System correction: negative stock fixed',
        userId: 1,
        referenceType: null,
        referenceId: null,
        unitPrice: $barang->harga_jual,
        metadata: [
            'system_correction' => true,
            'original_stock' => $barang->stok,
            'corrected_stock' => 0,
        ]
    );
}

echo "   Fixed " . $negativeStockItems->count() . " items with negative stock\n\n";

// 2. Fix stock valuations with negative values
echo "2. Fixing stock valuations with negative values...\n";
$negativeValuations = StockValuation::where('quantity_on_hand', '<', 0)
    ->orWhere('unit_cost', '<', 0)
    ->orWhere('unit_price', '<', 0)
    ->get();

foreach ($negativeValuations as $valuation) {
    $barangName = $valuation->barang ? $valuation->barang->nama : 'Unknown';
    echo "   - Fixing valuation for {$barangName}\n";
    
    if ($valuation->quantity_on_hand < 0) {
        $valuation->quantity_on_hand = 0;
    }
    if ($valuation->unit_cost < 0) {
        $valuation->unit_cost = 0;
    }
    if ($valuation->unit_price < 0) {
        $valuation->unit_price = 0;
    }
    
    $valuation->calculateValues();
}

echo "   Fixed " . $negativeValuations->count() . " stock valuations\n\n";

// 3. Recalculate stock movements that might have wrong quantities
echo "3. Recalculating stock movement values...\n";
$movements = StockMovement::whereNull('stock_value_change')
    ->orWhere('stock_value_change', 0)
    ->with('barang')
    ->get();

foreach ($movements as $movement) {
    if ($movement->barang) {
        echo "   - Recalculating movement for {$movement->barang->nama}\n";
        $movement->calculateStockValues();
        $movement->save();
    }
}

echo "   Recalculated " . $movements->count() . " stock movements\n\n";

// 4. Validate stock consistency
echo "4. Validating stock consistency...\n";
$inconsistentItems = [];

foreach (Barang::where('is_active', true)->get() as $barang) {
    $lastMovement = $barang->stockMovements()->latest()->first();
    
    if ($lastMovement && $lastMovement->stock_after != $barang->stok) {
        $inconsistentItems[] = [
            'barang' => $barang->nama,
            'current_stock' => $barang->stok,
            'last_movement_stock' => $lastMovement->stock_after,
            'difference' => $barang->stok - $lastMovement->stock_after,
        ];
        
        // Fix the inconsistency
        echo "   - Fixing inconsistency for {$barang->nama}: {$lastMovement->stock_after} -> {$barang->stok}\n";
        
        $difference = $barang->stok - $lastMovement->stock_after;
        if ($difference != 0) {
            $barang->recordStockMovement(
                type: 'correction',
                quantity: abs($difference),
                description: 'System correction: stock inconsistency fixed',
                userId: 1,
                referenceType: null,
                referenceId: null,
                unitPrice: $barang->harga_jual,
                metadata: [
                    'system_correction' => true,
                    'movement_stock' => $lastMovement->stock_after,
                    'actual_stock' => $barang->stok,
                    'difference' => $difference,
                ]
            );
        }
    }
}

echo "   Found and fixed " . count($inconsistentItems) . " inconsistent items\n\n";

// 5. Summary
echo "✅ Stock Issues Fixed!\n";
echo "Summary:\n";
echo "- Negative stock items fixed: " . $negativeStockItems->count() . "\n";
echo "- Negative valuations fixed: " . $negativeValuations->count() . "\n";
echo "- Stock movements recalculated: " . $movements->count() . "\n";
echo "- Inconsistent items fixed: " . count($inconsistentItems) . "\n";

echo "\n🎉 All stock issues have been resolved!\n";
