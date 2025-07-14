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
            'username' => 'required|string|max:255|unique:pengguna',
            'phone_number' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone_number.regex' => 'Nomor HP hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign 'pelanggan' role to newly registered user
        $userRole = Role::where('name', Role::PELANGGAN)->first();
        if ($userRole) {
            $user->roles()->attach($userRole->id);
        }

        Auth::login($user);
        // Redirect langsung ke halaman utama setelah register
        return redirect('/');
    }
}
