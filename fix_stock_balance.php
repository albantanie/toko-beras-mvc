<?php

require_once 'vendor/autoload.php';

use App\Models\Barang;
use App\Models\DailyReport;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;

echo "=== Stock Balance Fix Tool ===\n\n";

$user = User::whereHas('roles', function($q) { $q->where('name', 'karyawan'); })->first();
if (!$user) {
    echo "No karyawan user found!\n";
    exit(1);
}

echo "Using user: {$user->name} (ID: {$user->id})\n\n";

// 1. Fix missing initial stock movements
echo "1. Fixing missing initial stock movements...\n";
$itemsWithoutMovements = Barang::whereDoesntHave('stockMovements')
    ->where('stok', '>', 0)
    ->get();

$fixedInitial = 0;
foreach ($itemsWithoutMovements as $barang) {
    try {
        StockMovement::create([
            'barang_id' => $barang->id,
            'type' => 'initial',
            'quantity' => $barang->stok,
            'stock_before' => 0,
            'stock_after' => $barang->stok,
            'unit_price' => $barang->harga_beli ?? 0,
            'total_value' => $barang->stok * ($barang->harga_beli ?? 0),
            'description' => 'Initial stock - auto-generated for balance fix',
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(30), // Backdate to ensure it's included
        ]);
        $fixedInitial++;
        echo "✓ Created initial movement for: {$barang->nama} (Stock: {$barang->stok})\n";
    } catch (Exception $e) {
        echo "✗ Failed to create movement for {$barang->nama}: {$e->getMessage()}\n";
    }
}

echo "Fixed {$fixedInitial} items with missing initial movements.\n\n";

// 2. Fix stock_after values in existing movements
echo "2. Fixing stock_after values in movements...\n";
$barangs = Barang::with('stockMovements')->get();
$fixedMovements = 0;

foreach ($barangs as $barang) {
    $movements = $barang->stockMovements()->orderBy('created_at')->get();
    $runningStock = 0;
    
    foreach ($movements as $movement) {
        $oldStockAfter = $movement->stock_after;
        
        // Calculate correct stock_after
        if ($movement->type === 'initial') {
            $runningStock = $movement->quantity;
        } elseif ($movement->type === 'in' || $movement->type === 'adjustment') {
            $runningStock += $movement->quantity;
        } elseif ($movement->type === 'out') {
            $runningStock -= abs($movement->quantity);
        }
        
        if ($movement->stock_after != $runningStock) {
            $movement->update([
                'stock_before' => $runningStock - $movement->quantity,
                'stock_after' => $runningStock
            ]);
            $fixedMovements++;
            echo "✓ Fixed {$barang->nama} movement: {$oldStockAfter} → {$runningStock}\n";
        }
    }
    
    // Update barang stock to match last movement
    if ($movements->count() > 0) {
        $lastMovement = $movements->last();
        if ($barang->stok != $lastMovement->stock_after) {
            $oldStock = $barang->stok;
            $barang->update(['stok' => $lastMovement->stock_after]);
            echo "✓ Updated {$barang->nama} stock: {$oldStock} → {$lastMovement->stock_after}\n";
        }
    }
}

echo "Fixed {$fixedMovements} movement records.\n\n";

// 3. Regenerate daily reports for August 2025
echo "3. Regenerating daily reports for August 2025...\n";
$august2025 = Carbon::create(2025, 8, 1);
$startDate = $august2025->startOfMonth();
$endDate = $august2025->endOfMonth();

$regeneratedReports = 0;
$currentDate = $startDate->copy();

while ($currentDate <= $endDate) {
    // Check if there are stock movements for this date
    $hasMovements = StockMovement::whereDate('created_at', $currentDate)->exists();
    
    if ($hasMovements) {
        try {
            // Force regenerate the report
            $report = DailyReport::generateStockReport($currentDate, $user->id, true);
            $regeneratedReports++;
            echo "✓ Regenerated report for: {$currentDate->format('Y-m-d')}\n";
        } catch (Exception $e) {
            echo "✗ Failed to regenerate report for {$currentDate->format('Y-m-d')}: {$e->getMessage()}\n";
        }
    }
    
    $currentDate->addDay();
}

echo "Regenerated {$regeneratedReports} daily reports.\n\n";

// 4. Verify the fix
echo "4. Verifying the fix...\n";

// Get updated data
$totalCurrentStock = Barang::sum('stok');
$totalCurrentValue = Barang::selectRaw('SUM(stok * harga_beli) as total')->first()->total ?? 0;

$dailyReports = DailyReport::where('type', 'stock')
    ->where('user_id', $user->id)
    ->whereBetween('report_date', [$startDate, $endDate])
    ->get();

$totalReportMovements = $dailyReports->sum('total_stock_movements');
$totalReportValue = $dailyReports->sum('total_stock_value');

echo "Current Stock (Barang table): " . number_format($totalCurrentStock) . " items, Rp " . number_format($totalCurrentValue) . "\n";
echo "Monthly Report Data: " . number_format($totalReportMovements) . " movements, Rp " . number_format($totalReportValue) . "\n";

$difference = abs($totalCurrentValue - $totalReportValue);
if ($difference < 1000) {
    echo "✅ SUCCESS: Values are now balanced (difference: Rp " . number_format($difference) . ")\n";
} else {
    echo "⚠️  Still some discrepancy: Rp " . number_format($difference) . "\n";
}

// 5. Generate new monthly report PDF
echo "\n5. Generating new monthly report PDF...\n";
try {
    $monthlySummary = DailyReport::generateMonthlySummary($august2025, 'stock', $user->id);
    
    if ($monthlySummary['reports_included'] > 0) {
        echo "✓ Monthly summary generated with {$monthlySummary['reports_included']} daily reports\n";
        echo "✓ Total movements: " . number_format($monthlySummary['summary']['total_movements']) . "\n";
        echo "✓ Total value: Rp " . number_format($monthlySummary['summary']['total_stock_value']) . "\n";
        
        // The PDF will be generated when accessed through the web interface
        echo "✓ Monthly report data is ready. Access it through the web interface to generate the PDF.\n";
    } else {
        echo "✗ No daily reports found for monthly summary\n";
    }
} catch (Exception $e) {
    echo "✗ Failed to generate monthly summary: {$e->getMessage()}\n";
}

echo "\n=== Fix Complete ===\n";
echo "Summary of changes:\n";
echo "- Created {$fixedInitial} initial stock movements\n";
echo "- Fixed {$fixedMovements} movement records\n";
echo "- Regenerated {$regeneratedReports} daily reports\n";
echo "\nThe monthly stock report should now balance with the karyawan dashboard data.\n";
