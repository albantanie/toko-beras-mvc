<?php

// Simple test for monthly report
echo "Testing Monthly Report System...\n";

// Test 1: Check if controller exists
$controllerPath = 'app/Http/Controllers/MonthlyReportController.php';
if (file_exists($controllerPath)) {
    echo "✅ MonthlyReportController exists\n";
} else {
    echo "❌ MonthlyReportController not found\n";
    exit;
}

// Test 2: Check if routes exist
$routesContent = file_get_contents('routes/web.php');
if (strpos($routesContent, 'monthly/preview') !== false) {
    echo "✅ Monthly preview route exists\n";
} else {
    echo "❌ Monthly preview route not found\n";
}

if (strpos($routesContent, 'monthly/generate') !== false) {
    echo "✅ Monthly generate route exists\n";
} else {
    echo "❌ Monthly generate route not found\n";
}

// Test 3: Check if frontend file exists
$frontendPath = 'resources/js/pages/laporan/monthly-generate.tsx';
if (file_exists($frontendPath)) {
    echo "✅ Frontend component exists\n";
} else {
    echo "❌ Frontend component not found\n";
}

// Test 4: Check if DailyReport model has generateMonthlySummary method
$modelContent = file_get_contents('app/Models/DailyReport.php');
if (strpos($modelContent, 'generateMonthlySummary') !== false) {
    echo "✅ generateMonthlySummary method exists\n";
} else {
    echo "❌ generateMonthlySummary method not found\n";
}

echo "\nAll basic checks completed!\n";
echo "Try accessing: http://localhost:8000/laporan/monthly/create\n";
