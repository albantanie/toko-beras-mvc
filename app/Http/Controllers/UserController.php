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

class UserController extends Controller
{
    /**
     * Display a listing of users (admin only).
     */
    public function index(): Response
    {
        $users = User::with('roles')->paginate(10);
        $roles = Role::all();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new user (admin only).
     */
    public function create(): Response
    {
        $roles = Role::whereIn('name', [Role::ADMIN, Role::OWNER, Role::KARYAWAN, Role::KASIR])->get();

        return Inertia::render('admin/users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user (admin only).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:'.implode(',', [Role::ADMIN, Role::OWNER, Role::KARYAWAN, Role::KASIR]),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Auto verify admin-created users
        ]);

        // Assign role to user
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dibuat dengan role ' . $request->role);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $user->load('roles');

        return Inertia::render('admin/users/show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $user->load('roles');
        $roles = Role::whereIn('name', [Role::ADMIN, Role::OWNER, Role::KARYAWAN, Role::KASIR])->get();

        return Inertia::render('admin/users/edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:'.implode(',', [Role::ADMIN, Role::OWNER, Role::KARYAWAN, Role::KASIR]),
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update user role
        $user->roles()->detach(); // Remove all current roles
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diupdate');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun sendiri');
        }

        $user->roles()->detach(); // Remove role relationships
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus');
    }
}
