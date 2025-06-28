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

/**
 * AuthenticatedSessionController - Controller untuk mengelola sesi autentikasi
 *
 * Controller ini menangani:
 * - Menampilkan halaman login
 * - Proses login dan validasi kredensial
 * - Logout dan pembersihan sesi
 * - Redirect berdasarkan role pengguna setelah login
 *
 * @package App\Http\Controllers\Auth
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan halaman login
     *
     * Menampilkan form login dengan status message jika ada
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Menangani proses autentikasi login
     *
     * Memvalidasi kredensial dan melakukan redirect berdasarkan role pengguna
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
