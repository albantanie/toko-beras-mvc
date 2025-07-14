<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller untuk mengelola data pengguna (user management)
 * Hanya dapat diakses oleh admin untuk mengelola user dengan role tertentu
 */
class UserController extends Controller
{
    /**
     * Menampilkan daftar semua pengguna dengan paginasi (khusus admin)
     *
     * @return Response Halaman daftar pengguna dengan data user dan role
     */
    public function index(): Response
    {
        // Ambil data user dengan relasi role, gunakan paginasi 10 item per halaman
        $users = User::with('roles')->paginate(10);

        // Ambil semua data role untuk filter atau keperluan lain
        $roles = Role::all();

        // Render halaman index dengan data user dan role
        return Inertia::render('admin/users/index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Menampilkan form untuk membuat pengguna baru (khusus admin)
     *
     * @return Response Halaman form pembuatan user baru
     */
    public function create(): Response
    {
        // Ambil role yang dapat dibuat oleh admin (tidak termasuk pelanggan)
        $roles = Role::whereIn('name', [Role::ADMIN, Role::OWNER, Role::KARYAWAN, Role::KASIR])->get();

        // Render halaman form create dengan data role
        return Inertia::render('admin/users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Menyimpan pengguna baru ke database (khusus admin)
     *
     * @param Request $request Data form pembuatan user
     * @return RedirectResponse Redirect ke halaman daftar user
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi input form
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:'.implode(',', [Role::ADMIN, Role::OWNER, Role::KARYAWAN, Role::KASIR]),
        ]);

        // Buat user baru dengan data yang sudah divalidasi
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Berikan role kepada user yang baru dibuat
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        // Redirect ke halaman daftar user dengan pesan sukses
        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dibuat dengan role ' . $request->role);
    }

    /**
     * Menampilkan detail pengguna tertentu
     *
     * @param User $user Model user yang akan ditampilkan
     * @return Response Halaman detail user
     */
    public function show(User $user): Response
    {
        // Load relasi role untuk user
        $user->load('roles');

        // Render halaman detail user
        return Inertia::render('admin/users/show', [
            'user' => $user,
        ]);
    }

    /**
     * Menampilkan form untuk mengedit pengguna tertentu
     *
     * @param User $user Model user yang akan diedit
     * @return Response Halaman form edit user
     */
    public function edit(User $user): Response
    {
        // Load relasi role untuk user yang akan diedit
        $user->load('roles');

        // Ambil role yang dapat dipilih untuk user
        $roles = Role::whereIn('name', [Role::ADMIN, Role::OWNER, Role::KARYAWAN, Role::KASIR])->get();

        // Render halaman form edit dengan data user dan role
        return Inertia::render('admin/users/edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Memperbarui data pengguna tertentu
     *
     * @param Request $request Data form update user
     * @param User $user Model user yang akan diupdate
     * @return RedirectResponse Redirect ke halaman daftar user
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Validasi input form dengan pengecualian email untuk user yang sedang diedit
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:pengguna,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:'.implode(',', [Role::ADMIN, Role::OWNER, Role::KARYAWAN, Role::KASIR]),
        ]);

        // Siapkan data yang akan diupdate (nama dan email wajib)
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        // Jika password diisi, tambahkan ke data update dengan hash
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Update data user
        $user->update($updateData);

        // Update role user: hapus role lama dan berikan role baru
        $user->roles()->detach(); // Hapus semua role yang ada
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->roles()->attach($role->id); // Berikan role baru
        }

        // Redirect ke halaman daftar user dengan pesan sukses
        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diupdate');
    }

    /**
     * Menghapus pengguna tertentu dari database
     *
     * @param User $user Model user yang akan dihapus
     * @return RedirectResponse Redirect ke halaman daftar user
     */
    public function destroy(User $user): RedirectResponse
    {
        // Cegah admin menghapus akun mereka sendiri
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun sendiri');
        }

        // Hapus relasi role terlebih dahulu sebelum menghapus user
        $user->roles()->detach();

        // Hapus user dari database
        $user->delete();

        // Redirect ke halaman daftar user dengan pesan sukses
        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus');
    }
}
