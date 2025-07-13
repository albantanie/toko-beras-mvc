<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test route without any middleware
Route::post('/test', function (Request $request) {
    return response()->json([
        'message' => 'API test works!',
        'data' => $request->all()
    ]);
});

// Simple login API with session middleware
Route::post('/auth/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $credentials['email'])->first();

    if (!$user || !\Hash::check($credentials['password'], $user->password)) {
        return response()->json(['error' => 'Email atau password salah'], 422);
    }

    Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate();

    // Load user with roles
    $user = $user->load('roles');
    $userRole = $user->roles->first()?->name;

    // Determine redirect URL
    $redirectUrl = match($userRole) {
        'owner' => '/owner/dashboard',
        'admin' => '/admin/dashboard',
        'kasir' => '/kasir/dashboard',
        'karyawan' => '/karyawan/dashboard',
        'pelanggan' => '/user/dashboard',
        default => '/dashboard'
    };

    return response()->json([
        'success' => true,
        'redirect' => $redirectUrl,
        'user' => $user->name,
        'role' => $userRole
    ]);
})->middleware(['web']);

// Logout API with session middleware
Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();

    // Clear cart when user logs out
    $request->session()->forget('cart');

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['success' => true, 'redirect' => '/']);
})->middleware(['web']);
