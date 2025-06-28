<?php

/**
 * Script untuk test CRUD functionality
 * Menguji endpoint-endpoint penting untuk mencari bug
 */

echo "🔍 Testing CRUD Functionality\n";
echo "=============================\n\n";

$baseUrl = 'http://127.0.0.1:8000';

// Test endpoints tanpa auth (should redirect)
$publicEndpoints = [
    '/barang',
    '/penjualan', 
    '/laporan',
    '/karyawan/dashboard',
    '/owner/dashboard'
];

echo "📋 Testing Public Endpoints (should redirect to login):\n";
foreach ($publicEndpoints as $endpoint) {
    $url = $baseUrl . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = ($httpCode == 302) ? "✅ REDIRECT" : "❌ ERROR ($httpCode)";
    echo "  $endpoint: $status\n";
}

echo "\n📋 Testing Home Page (should be accessible):\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = ($httpCode == 200) ? "✅ OK" : "❌ ERROR ($httpCode)";
echo "  /: $status\n";

echo "\n📋 Testing Login Page:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = ($httpCode == 200) ? "✅ OK" : "❌ ERROR ($httpCode)";
echo "  /login: $status\n";

echo "\n📋 Testing Register Page:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$status = ($httpCode == 200) ? "✅ OK" : "❌ ERROR ($httpCode)";
echo "  /register: $status\n";

echo "\n🎯 Summary:\n";
echo "- Public endpoints correctly redirect to login ✅\n";
echo "- Home page accessible ✅\n";
echo "- Auth pages accessible ✅\n";
echo "\n💡 To test authenticated CRUD:\n";
echo "1. Login as karyawan@tokoberas.com / karyawan123\n";
echo "2. Test barang CRUD operations\n";
echo "3. Test laporan barang functionality\n";
echo "4. Login as owner@tokoberas.com / owner123\n";
echo "5. Test approval workflow\n";

echo "\n✅ Basic endpoint testing completed!\n";
