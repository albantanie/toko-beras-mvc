<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Get the authenticated user
        $user = Auth::user();

        // Redirect based on user role
        if ($user->hasRole(Role::ADMIN)) {
            return redirect()->intended(route('admin.dashboard'));
        } elseif ($user->hasRole(Role::KASIR)) {
            return redirect()->intended(route('kasir.dashboard'));
        } elseif ($user->hasRole(Role::KARYAWAN)) {
            return redirect()->intended(route('karyawan.dashboard'));
        } elseif ($user->hasRole(Role::OWNER)) {
            return redirect()->intended(route('owner.dashboard'));
        } elseif ($user->hasRole(Role::PELANGGAN)) {
            return redirect()->intended(route('user.dashboard'));
        }

        // Fallback to home page if no specific role
        return redirect()->intended('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Clear cart when user logs out
        $request->session()->forget('cart');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
