<?php

require_once 'vendor/autoload.php';

use App\Models\Barang;
use App\Models\DailyReport;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;

echo "=== Stock Balance Diagnostic Tool ===\n\n";

// 1. Get current stock from Barang table (what karyawan sees)
echo "1. Current Stock from Barang Table (Karyawan Dashboard Source):\n";
$barangs = Barang::select('id', 'nama', 'stok', 'harga_beli', 'harga_jual')->get();
$totalCurrentStock = $barangs->sum('stok');
$totalCurrentValue = $barangs->sum(function($barang) {
    return $barang->stok * $barang->harga_beli;
});

echo "Total Items: " . $barangs->count() . "\n";
echo "Total Stock Quantity: " . number_format($totalCurrentStock) . "\n";
echo "Total Stock Value: Rp " . number_format($totalCurrentValue) . "\n\n";

// 2. Get August 2025 monthly report data
echo "2. August 2025 Monthly Report Data:\n";
$august2025 = Carbon::create(2025, 8, 1);
$user = User::whereHas('roles', function($q) { $q->where('name', 'karyawan'); })->first();

if (!$user) {
    echo "No karyawan user found!\n";
    exit(1);
}

// Get daily reports for August 2025
$dailyReports = DailyReport::where('type', 'stock')
    ->where('user_id', $user->id)
    ->where('status', 'completed')
    ->whereBetween('report_date', [$august2025->startOfMonth(), $august2025->endOfMonth()])
    ->get();

echo "Daily reports found: " . $dailyReports->count() . "\n";

$totalReportMovements = $dailyReports->sum('total_stock_movements');
$totalReportValue = $dailyReports->sum('total_stock_value');

echo "Total movements in reports: " . number_format($totalReportMovements) . "\n";
echo "Total value in reports: Rp " . number_format($totalReportValue) . "\n\n";

// 3. Get actual stock movements for August 2025
echo "3. Actual Stock Movements for August 2025:\n";
$stockMovements = StockMovement::whereBetween('created_at', [$august2025->startOfMonth(), $august2025->endOfMonth()])
    ->with('barang')
    ->get();

echo "Total stock movements: " . $stockMovements->count() . "\n";

$movementsByType = $stockMovements->groupBy('type');
foreach ($movementsByType as $type => $movements) {
    $totalQty = $movements->sum('quantity');
    echo "- {$type}: " . $movements->count() . " movements, " . number_format($totalQty) . " quantity\n";
}

$totalMovementValue = $stockMovements->sum('total_value');
echo "Total movement value: Rp " . number_format($totalMovementValue) . "\n\n";

// 4. Check for discrepancies
echo "4. Discrepancy Analysis:\n";

// Compare current stock with what should be based on movements
echo "=== Stock Reconciliation ===\n";
foreach ($barangs->take(10) as $barang) {
    $movements = StockMovement::where('barang_id', $barang->id)->orderBy('created_at')->get();
    
    if ($movements->count() > 0) {
        $lastMovement = $movements->last();
        $calculatedStock = $lastMovement->stock_after;
        $actualStock = $barang->stok;
        
        if ($calculatedStock != $actualStock) {
            echo "MISMATCH: {$barang->nama}\n";
            echo "  - Actual stock: {$actualStock}\n";
            echo "  - Last movement stock_after: {$calculatedStock}\n";
            echo "  - Difference: " . ($actualStock - $calculatedStock) . "\n";
        }
    } else {
        if ($barang->stok > 0) {
            echo "NO MOVEMENTS: {$barang->nama} has stock {$barang->stok} but no movements\n";
        }
    }
}

// 5. Check daily report data integrity
echo "\n=== Daily Report Data Integrity ===\n";
foreach ($dailyReports->take(5) as $report) {
    $reportDate = Carbon::parse($report->report_date);
    $actualMovements = StockMovement::whereDate('created_at', $reportDate)->get();
    
    $reportData = $report->data;
    $reportMovementsCount = count($reportData['movements'] ?? []);
    $actualMovementsCount = $actualMovements->count();
    
    echo "Date: {$reportDate->format('Y-m-d')}\n";
    echo "  - Report shows: {$reportMovementsCount} movements\n";
    echo "  - Actual movements: {$actualMovementsCount} movements\n";
    
    if ($reportMovementsCount != $actualMovementsCount) {
        echo "  - MISMATCH!\n";
    }
}

// 6. Suggest fixes
echo "\n=== Suggested Fixes ===\n";

$itemsWithoutMovements = $barangs->filter(function($barang) {
    return $barang->stok > 0 && StockMovement::where('barang_id', $barang->id)->count() == 0;
});

if ($itemsWithoutMovements->count() > 0) {
    echo "1. Items with stock but no movements: " . $itemsWithoutMovements->count() . "\n";
    echo "   - Run: php artisan stock:fix-missing-initial\n";
}

$outdatedReports = DailyReport::where('type', 'stock')
    ->where('user_id', $user->id)
    ->whereBetween('report_date', [$august2025->startOfMonth(), $august2025->endOfMonth()])
    ->get()
    ->filter(function($report) {
        $reportDate = Carbon::parse($report->report_date);
        $actualMovements = StockMovement::whereDate('created_at', $reportDate)->count();
        $reportMovements = count($report->data['movements'] ?? []);
        return $actualMovements != $reportMovements;
    });

if ($outdatedReports->count() > 0) {
    echo "2. Outdated daily reports: " . $outdatedReports->count() . "\n";
    echo "   - Regenerate these reports with force_regenerate=true\n";
}

echo "\n=== Summary ===\n";
echo "Current Stock (Karyawan View): " . number_format($totalCurrentStock) . " items, Rp " . number_format($totalCurrentValue) . "\n";
echo "Monthly Report Data: " . number_format($totalReportMovements) . " movements, Rp " . number_format($totalReportValue) . "\n";
echo "Actual Movements: " . $stockMovements->count() . " movements, Rp " . number_format($totalMovementValue) . "\n";

if (abs($totalCurrentValue - $totalReportValue) > 1000) {
    echo "\n⚠️  SIGNIFICANT DISCREPANCY DETECTED!\n";
    echo "Difference: Rp " . number_format(abs($totalCurrentValue - $totalReportValue)) . "\n";
} else {
    echo "\n✅ Values are reasonably close.\n";
}

echo "\n=== Diagnostic Complete ===\n";
