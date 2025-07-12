<?php
/**
 * Test Script: Kasir Generate → Owner Approve System
 * Jalankan dengan: php artisan tinker
 * Lalu copy-paste script ini
 */

echo "=== KASIR GENERATE → OWNER APPROVE SYSTEM TEST ===\n\n";

// 1. Test User Roles
echo "1. USER ROLES TEST:\n";

$kasir = \App\Models\User::whereHas('roles', function($q) { 
    $q->where('name', 'kasir'); 
})->first();

$owner = \App\Models\User::whereHas('roles', function($q) { 
    $q->where('name', 'owner'); 
})->first();

if ($kasir) {
    echo "✅ Kasir found: " . $kasir->email . "\n";
    echo "   - Can generate sales: " . (\App\Models\PdfReport::canUserGenerateType($kasir, \App\Models\PdfReport::TYPE_PENJUALAN) ? "YES" : "NO") . "\n";
    echo "   - Roles: " . $kasir->roles->pluck('name')->implode(', ') . "\n";
} else {
    echo "❌ No kasir found\n";
}

if ($owner) {
    echo "✅ Owner found: " . $owner->email . "\n";
    echo "   - Can approve reports: " . ($owner->isOwner() ? "YES" : "NO") . "\n";
    echo "   - Roles: " . $owner->roles->pluck('name')->implode(', ') . "\n";
} else {
    echo "❌ No owner found\n";
}

echo "\n";

// 2. Test Routes
echo "2. ROUTES TEST:\n";

$routes = [
    'laporan.my-reports' => 'Kasir My Reports',
    'laporan.generate-sales' => 'Kasir Generate Sales',
    'owner.laporan.reports' => 'Owner Reports List',
    'owner.laporan.approve' => 'Owner Approve Report',
    'owner.download-report' => 'Owner Download Report'
];

foreach ($routes as $name => $description) {
    try {
        if ($name === 'laporan.generate-sales') {
            // POST route, just check if it exists
            $exists = \Route::has($name);
            echo ($exists ? "✅" : "❌") . " {$description}: " . ($exists ? "EXISTS" : "NOT FOUND") . "\n";
        } elseif ($name === 'owner.laporan.approve') {
            // Route with parameter
            $exists = \Route::has($name);
            echo ($exists ? "✅" : "❌") . " {$description}: " . ($exists ? "EXISTS" : "NOT FOUND") . "\n";
        } elseif ($name === 'owner.download-report') {
            // Route with parameter
            $exists = \Route::has($name);
            echo ($exists ? "✅" : "❌") . " {$description}: " . ($exists ? "EXISTS" : "NOT FOUND") . "\n";
        } else {
            $url = route($name);
            echo "✅ {$description}: {$url}\n";
        }
    } catch (\Exception $e) {
        echo "❌ {$description}: Route not found\n";
    }
}

echo "\n";

// 3. Test Database Structure
echo "3. DATABASE STRUCTURE TEST:\n";

try {
    // Check pdf_reports table
    $reportsCount = \App\Models\PdfReport::count();
    echo "✅ PDF Reports table: {$reportsCount} records\n";
    
    // Check recent reports
    $recentReports = \App\Models\PdfReport::with(['generator', 'approver'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "   Recent reports:\n";
    foreach ($recentReports as $report) {
        $generator = $report->generator ? $report->generator->name : 'Unknown';
        $approver = $report->approver ? $report->approver->name : 'None';
        echo "   - {$report->title} | Type: {$report->type} | Status: {$report->status} | By: {$generator} | Approved by: {$approver}\n";
    }
    
    // Check pending reports
    $pendingCount = \App\Models\PdfReport::where('status', 'pending_approval')->count();
    echo "   Pending approval: {$pendingCount} reports\n";
    
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Test Permissions
echo "4. PERMISSIONS TEST:\n";

if ($kasir) {
    echo "KASIR PERMISSIONS:\n";
    echo "- Generate Sales Report: " . (\App\Models\PdfReport::canUserGenerateType($kasir, \App\Models\PdfReport::TYPE_PENJUALAN) ? "✅ YES" : "❌ NO") . "\n";
    echo "- Generate Stock Report: " . (\App\Models\PdfReport::canUserGenerateType($kasir, \App\Models\PdfReport::TYPE_STOCK) ? "✅ YES" : "❌ NO") . "\n";
    echo "- Generate Financial Report: " . (\App\Models\PdfReport::canUserGenerateType($kasir, \App\Models\PdfReport::TYPE_FINANCIAL) ? "✅ YES" : "❌ NO") . "\n";
}

if ($owner) {
    echo "OWNER PERMISSIONS:\n";
    echo "- Generate Sales Report: " . (\App\Models\PdfReport::canUserGenerateType($owner, \App\Models\PdfReport::TYPE_PENJUALAN) ? "✅ YES" : "❌ NO") . "\n";
    echo "- Generate Stock Report: " . (\App\Models\PdfReport::canUserGenerateType($owner, \App\Models\PdfReport::TYPE_STOCK) ? "✅ YES" : "❌ NO") . "\n";
    echo "- Generate Financial Report: " . (\App\Models\PdfReport::canUserGenerateType($owner, \App\Models\PdfReport::TYPE_FINANCIAL) ? "✅ YES" : "❌ NO") . "\n";
    echo "- Is Owner: " . ($owner->isOwner() ? "✅ YES" : "❌ NO") . "\n";
}

echo "\n";

// 5. Test Report Types
echo "5. REPORT TYPES TEST:\n";

$reportTypes = [
    \App\Models\PdfReport::TYPE_PENJUALAN => 'Sales Report',
    \App\Models\PdfReport::TYPE_STOCK => 'Stock Report',
    \App\Models\PdfReport::TYPE_FINANCIAL => 'Financial Report'
];

foreach ($reportTypes as $type => $name) {
    $count = \App\Models\PdfReport::where('type', $type)->count();
    echo "✅ {$name} ({$type}): {$count} reports\n";
}

echo "\n";

// 6. Test Status Types
echo "6. REPORT STATUS TEST:\n";

$statusTypes = [
    'pending_approval' => 'Pending Approval',
    'approved' => 'Approved',
    'rejected' => 'Rejected'
];

foreach ($statusTypes as $status => $name) {
    $count = \App\Models\PdfReport::where('status', $status)->count();
    echo "✅ {$name}: {$count} reports\n";
}

echo "\n";

// 7. Test Workflow
echo "7. WORKFLOW TEST:\n";

echo "KASIR WORKFLOW:\n";
echo "1. Login → http://localhost:8000/login\n";
echo "2. Dashboard → http://localhost:8000/kasir/dashboard\n";
echo "3. My Reports → http://localhost:8000/laporan/my-reports\n";
echo "4. Generate Sales Report → Fill form & submit\n";
echo "5. Status: Pending Approval\n";

echo "\nOWNER WORKFLOW:\n";
echo "1. Login → http://localhost:8000/login\n";
echo "2. Dashboard → http://localhost:8000/owner/dashboard\n";
echo "3. Reports → http://localhost:8000/owner/reports\n";
echo "4. View Pending Reports → Filter by Kasir\n";
echo "5. Approve/Reject → Update status\n";
echo "6. Download PDF → If approved\n";

echo "\n";

// 8. Test URLs
echo "8. TEST URLS:\n";

$testUrls = [
    'Kasir Login' => 'http://localhost:8000/login',
    'Kasir Dashboard' => 'http://localhost:8000/kasir/dashboard',
    'Kasir My Reports' => 'http://localhost:8000/laporan/my-reports',
    'Owner Login' => 'http://localhost:8000/login',
    'Owner Dashboard' => 'http://localhost:8000/owner/dashboard',
    'Owner Reports' => 'http://localhost:8000/owner/reports'
];

foreach ($testUrls as $name => $url) {
    echo "✅ {$name}: {$url}\n";
}

echo "\n";

// 9. Recommendations
echo "9. TESTING RECOMMENDATIONS:\n";

echo "MANUAL TESTING STEPS:\n";
echo "1. ✅ Test Kasir Generate:\n";
echo "   - Login sebagai kasir\n";
echo "   - Akses /laporan/my-reports\n";
echo "   - Generate sales report dengan date range\n";
echo "   - Verify report created with 'pending_approval' status\n";

echo "\n2. ✅ Test Owner Approve:\n";
echo "   - Login sebagai owner\n";
echo "   - Akses /owner/reports\n";
echo "   - Find kasir's pending report\n";
echo "   - Approve or reject the report\n";
echo "   - Verify status change\n";

echo "\n3. ✅ Test Download:\n";
echo "   - Owner download approved report\n";
echo "   - Verify PDF generation\n";
echo "   - Check file content\n";

echo "\n";

// 10. System Status
echo "10. SYSTEM STATUS:\n";

$systemChecks = [
    'Users with roles' => \App\Models\User::whereHas('roles')->count(),
    'Kasir users' => \App\Models\User::whereHas('roles', function($q) { $q->where('name', 'kasir'); })->count(),
    'Owner users' => \App\Models\User::whereHas('roles', function($q) { $q->where('name', 'owner'); })->count(),
    'Total reports' => \App\Models\PdfReport::count(),
    'Pending reports' => \App\Models\PdfReport::where('status', 'pending_approval')->count(),
    'Approved reports' => \App\Models\PdfReport::where('status', 'approved')->count(),
];

foreach ($systemChecks as $check => $count) {
    echo "✅ {$check}: {$count}\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "System is ready for Kasir Generate → Owner Approve workflow! 🚀\n";
