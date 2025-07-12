<?php
/**
 * Debug CSRF and Session Issues
 * Jalankan dengan: php artisan tinker
 * Lalu copy-paste script ini
 */

echo "=== CSRF & SESSION DEBUG ===\n\n";

// 1. Check Session Configuration
echo "1. SESSION CONFIGURATION:\n";
echo "- Driver: " . config('session.driver') . "\n";
echo "- Lifetime: " . config('session.lifetime') . " minutes\n";
echo "- Encrypt: " . (config('session.encrypt') ? 'YES' : 'NO') . "\n";
echo "- Path: " . config('session.path') . "\n";
echo "- Domain: " . (config('session.domain') ?: 'NULL') . "\n";
echo "- Same Site: " . (config('session.same_site') ?: 'NULL') . "\n";

// 2. Check Database Sessions
if (config('session.driver') === 'database') {
    try {
        $sessionCount = \DB::table('sessions')->count();
        echo "- Sessions in DB: " . $sessionCount . "\n";
        
        // Get recent sessions
        $recentSessions = \DB::table('sessions')
            ->orderBy('last_activity', 'desc')
            ->limit(5)
            ->get(['id', 'user_id', 'last_activity']);
        
        echo "- Recent sessions:\n";
        foreach ($recentSessions as $session) {
            $lastActivity = date('Y-m-d H:i:s', $session->last_activity);
            echo "  * ID: " . substr($session->id, 0, 10) . "... | User: " . ($session->user_id ?: 'Guest') . " | Last: {$lastActivity}\n";
        }
    } catch (\Exception $e) {
        echo "- Session DB Error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 3. Check CSRF Configuration
echo "2. CSRF CONFIGURATION:\n";
echo "- App Key Set: " . (config('app.key') ? 'YES' : 'NO') . "\n";
echo "- App Debug: " . (config('app.debug') ? 'YES' : 'NO') . "\n";

// 4. Test Route Access
echo "\n3. ROUTE ACCESS TEST:\n";

$testRoutes = [
    'laporan.my-reports' => 'GET',
    'laporan.generate-sales' => 'POST'
];

foreach ($testRoutes as $routeName => $method) {
    try {
        if (\Route::has($routeName)) {
            $route = \Route::getRoutes()->getByName($routeName);
            $uri = $route->uri();
            $middleware = implode(', ', $route->gatherMiddleware());
            echo "✅ {$routeName}: {$method} /{$uri}\n";
            echo "   Middleware: {$middleware}\n";
        } else {
            echo "❌ {$routeName}: Route not found\n";
        }
    } catch (\Exception $e) {
        echo "❌ {$routeName}: Error - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 5. Test User Authentication
echo "4. USER AUTHENTICATION TEST:\n";

// Find kasir user
$kasir = \App\Models\User::whereHas('roles', function($q) { 
    $q->where('name', 'kasir'); 
})->first();

if ($kasir) {
    echo "✅ Kasir found: " . $kasir->email . "\n";
    
    // Simulate login
    try {
        \Auth::login($kasir);
        echo "✅ Kasir login simulated\n";
        echo "- Auth check: " . (\Auth::check() ? 'YES' : 'NO') . "\n";
        echo "- User ID: " . \Auth::id() . "\n";
        echo "- User roles: " . \Auth::user()->roles->pluck('name')->implode(', ') . "\n";
        
        // Test permissions
        echo "- Can generate sales: " . (\App\Models\PdfReport::canUserGenerateType(\Auth::user(), \App\Models\PdfReport::TYPE_PENJUALAN) ? 'YES' : 'NO') . "\n";
        
    } catch (\Exception $e) {
        echo "❌ Login simulation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ No kasir user found\n";
}

echo "\n";

// 6. Test CSRF Token Generation
echo "5. CSRF TOKEN TEST:\n";

try {
    $token = csrf_token();
    echo "✅ CSRF Token generated: " . substr($token, 0, 20) . "...\n";
    echo "- Token length: " . strlen($token) . " characters\n";
    
    // Test token validation (simulate)
    $sessionToken = session()->token();
    echo "- Session token: " . substr($sessionToken, 0, 20) . "...\n";
    echo "- Tokens match: " . ($token === $sessionToken ? 'YES' : 'NO') . "\n";
    
} catch (\Exception $e) {
    echo "❌ CSRF Token error: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Test Session Data
echo "6. SESSION DATA TEST:\n";

try {
    $sessionId = session()->getId();
    echo "✅ Session ID: " . substr($sessionId, 0, 20) . "...\n";
    
    // Test session storage
    session(['test_key' => 'test_value']);
    $testValue = session('test_key');
    echo "- Session storage test: " . ($testValue === 'test_value' ? 'PASS' : 'FAIL') . "\n";
    
    // Check session lifetime
    $sessionData = session()->all();
    echo "- Session data keys: " . implode(', ', array_keys($sessionData)) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "\n";
}

echo "\n";

// 8. Recommendations
echo "7. RECOMMENDATIONS:\n";

echo "BROWSER TESTING:\n";
echo "1. Clear browser cache completely (Ctrl+Shift+Delete)\n";
echo "2. Open incognito/private window\n";
echo "3. Login fresh: http://localhost:8000/login\n";
echo "4. Check browser console for JavaScript errors\n";
echo "5. Check Network tab for 419 errors\n";

echo "\nSERVER TESTING:\n";
echo "1. Restart Laravel server: php artisan serve\n";
echo "2. Check storage/logs/laravel.log for errors\n";
echo "3. Verify .env SESSION_* settings\n";
echo "4. Test with different browser\n";

echo "\nDEBUG STEPS:\n";
echo "1. Add dd(\$request->all()) in generateSalesReport method\n";
echo "2. Check if CSRF token is being sent\n";
echo "3. Verify session is maintained between requests\n";
echo "4. Test with Postman/curl with proper headers\n";

echo "\n";

// 9. Generate Test URLs
echo "8. TEST URLS:\n";
echo "- Login: http://localhost:8000/login\n";
echo "- Kasir Dashboard: http://localhost:8000/kasir/dashboard\n";
echo "- My Reports: http://localhost:8000/laporan/my-reports\n";
echo "- Logout: http://localhost:8000/logout\n";

echo "\n=== DEBUG COMPLETE ===\n";

// Cleanup
if (\Auth::check()) {
    \Auth::logout();
    echo "Auth session cleaned up.\n";
}
