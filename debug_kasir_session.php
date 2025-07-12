<?php
/**
 * Debug Script untuk Kasir Session Issues
 * Jalankan dengan: php artisan tinker
 * Lalu copy-paste script ini
 */

echo "=== KASIR SESSION DEBUG ===\n\n";

// 1. Check Authentication
echo "1. AUTHENTICATION CHECK:\n";
echo "- Auth Check: " . (auth()->check() ? "✅ LOGGED IN" : "❌ NOT LOGGED IN") . "\n";

if (auth()->check()) {
    $user = auth()->user();
    echo "- User ID: " . $user->id . "\n";
    echo "- User Name: " . $user->name . "\n";
    echo "- User Email: " . $user->email . "\n";
    
    // Check roles
    $roles = $user->roles->pluck('name')->toArray();
    echo "- User Roles: " . implode(', ', $roles) . "\n";
    echo "- Is Kasir: " . ($user->hasRole('kasir') ? "✅ YES" : "❌ NO") . "\n";
    echo "- Can Generate Sales: " . (\App\Models\PdfReport::canUserGenerateType($user, \App\Models\PdfReport::TYPE_PENJUALAN) ? "✅ YES" : "❌ NO") . "\n";
} else {
    echo "❌ User not authenticated. Please login first.\n";
}

echo "\n";

// 2. Check Session
echo "2. SESSION CHECK:\n";
echo "- Session ID: " . session()->getId() . "\n";
echo "- Session Driver: " . config('session.driver') . "\n";
echo "- Session Lifetime: " . config('session.lifetime') . " minutes\n";
echo "- CSRF Token: " . csrf_token() . "\n";

// Check if session table exists (for database driver)
if (config('session.driver') === 'database') {
    try {
        $sessionCount = \DB::table('sessions')->count();
        echo "- Sessions in DB: " . $sessionCount . "\n";
        
        // Check current session
        $currentSession = \DB::table('sessions')
            ->where('id', session()->getId())
            ->first();
        
        if ($currentSession) {
            echo "- Current Session Found: ✅ YES\n";
            echo "- Session Last Activity: " . date('Y-m-d H:i:s', $currentSession->last_activity) . "\n";
        } else {
            echo "- Current Session Found: ❌ NO\n";
        }
    } catch (\Exception $e) {
        echo "- Session Table Error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 3. Check Routes
echo "3. ROUTES CHECK:\n";
$routes = [
    'laporan.my-reports' => 'GET /laporan/my-reports',
    'laporan.generate-sales' => 'POST /laporan/generate-sales',
    'kasir.dashboard' => 'GET /kasir/dashboard'
];

foreach ($routes as $name => $description) {
    try {
        $url = route($name);
        echo "- {$description}: ✅ {$url}\n";
    } catch (\Exception $e) {
        echo "- {$description}: ❌ Route not found\n";
    }
}

echo "\n";

// 4. Check Database Connection
echo "4. DATABASE CHECK:\n";
try {
    \DB::connection()->getPdo();
    echo "- Database Connection: ✅ CONNECTED\n";
    
    // Check users table
    $userCount = \DB::table('users')->count();
    echo "- Users Count: " . $userCount . "\n";
    
    // Check roles table
    $roleCount = \DB::table('roles')->count();
    echo "- Roles Count: " . $roleCount . "\n";
    
    // Check kasir users
    $kasirUsers = \DB::table('users')
        ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('roles.name', 'kasir')
        ->select('users.name', 'users.email')
        ->get();
    
    echo "- Kasir Users:\n";
    foreach ($kasirUsers as $kasir) {
        echo "  * {$kasir->name} ({$kasir->email})\n";
    }
    
} catch (\Exception $e) {
    echo "- Database Connection: ❌ ERROR - " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Check Environment
echo "5. ENVIRONMENT CHECK:\n";
echo "- App Environment: " . app()->environment() . "\n";
echo "- App Debug: " . (config('app.debug') ? "✅ ENABLED" : "❌ DISABLED") . "\n";
echo "- App Key Set: " . (config('app.key') ? "✅ YES" : "❌ NO") . "\n";

echo "\n";

// 6. Generate Test URLs
echo "6. TEST URLS:\n";
echo "- Login: " . url('/login') . "\n";
echo "- Logout: " . url('/logout') . "\n";
echo "- Kasir Dashboard: " . url('/kasir/dashboard') . "\n";
echo "- My Reports: " . url('/laporan/my-reports') . "\n";

echo "\n";

// 7. Recommendations
echo "7. RECOMMENDATIONS:\n";

if (!auth()->check()) {
    echo "❌ LOGIN REQUIRED:\n";
    echo "   1. Go to: " . url('/login') . "\n";
    echo "   2. Use kasir credentials\n";
    echo "   3. Should redirect to: " . url('/kasir/dashboard') . "\n";
} else {
    $user = auth()->user();
    if (!$user->hasRole('kasir')) {
        echo "❌ WRONG ROLE:\n";
        echo "   Current roles: " . implode(', ', $user->roles->pluck('name')->toArray()) . "\n";
        echo "   Need: kasir role\n";
        echo "   Login with kasir account\n";
    } else {
        echo "✅ AUTHENTICATION OK:\n";
        echo "   1. Go to: " . url('/laporan/my-reports') . "\n";
        echo "   2. Fill 'Laporan Penjualan' form\n";
        echo "   3. Click 'Generate Laporan Penjualan'\n";
        echo "   4. Confirm in SweetAlert2\n";
    }
}

echo "\n";

// 8. Quick Fixes
echo "8. QUICK FIXES:\n";
echo "- Clear cache: php artisan cache:clear\n";
echo "- Clear config: php artisan config:clear\n";
echo "- Clear sessions: php artisan session:flush\n";
echo "- Clear browser cache: Ctrl+Shift+Delete\n";

echo "\n=== DEBUG COMPLETE ===\n";
