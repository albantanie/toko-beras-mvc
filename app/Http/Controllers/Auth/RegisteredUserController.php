<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RegisteredUserController - Controller untuk mengelola registrasi pengguna baru
 *
 * Controller ini menangani:
 * - Menampilkan halaman registrasi
 * - Proses registrasi pengguna baru dengan role 'pelanggan'
 * - Validasi data registrasi
 * - Auto-login setelah registrasi berhasil
 *
 * @package App\Http\Controllers\Auth
 */
class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign 'pelanggan' role to newly registered user
        $userRole = Role::where('name', Role::PELANGGAN)->first();
        if ($userRole) {
            $user->roles()->attach($userRole->id);
        }

        event(new Registered($user));

        Auth::login($user);

        // Redirect new user (pelanggan) to user dashboard
        return redirect()->intended(route('user.dashboard'));
    }
}
