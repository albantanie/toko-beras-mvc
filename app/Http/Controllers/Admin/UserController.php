<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $role = $request->get('role');
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        $query = User::with('roles');

        // Exclude walk-in system user from admin list
        $query->where('email', '!=', 'walkin@tokoberas.internal');

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Role filter
        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Sorting
        $allowedSorts = ['name', 'email', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $users = $query->paginate(5)->withQueryString();
        $roles = Role::all();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create()
    {
        $roles = Role::all();

        return Inertia::render('admin/users/create', [
            'roles' => $roles,
        ]);
    }

    public function quickAdd()
    {
        $roles = Role::all();

        return Inertia::render('admin/users/quick-add', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'phone_number' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'role_id' => 'required|exists:roles,id',
            ], [
                'name.required' => 'Nama harus diisi.',
                'username.required' => 'Username harus diisi.',
                'username.unique' => 'Username sudah digunakan.',
                'phone_number.required' => 'Nomor HP harus diisi.',
                'address.required' => 'Alamat harus diisi.',
                'email.required' => 'Email harus diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan.',
                'password.required' => 'Password harus diisi.',
                'password.confirmed' => 'Konfirmasi password tidak cocok.',
                'role_id.required' => 'Role harus dipilih.',
                'role_id.exists' => 'Role yang dipilih tidak valid.',
            ]);

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            // Attach role to user
            $user->roles()->attach($request->role_id);

            return redirect()->route('admin.users.index')
                ->with('success', 'User berhasil dibuat.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal membuat user: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(User $user)
    {
        // Prevent viewing walk-in system user
        if ($user->email === 'walkin@tokoberas.internal') {
            return redirect()->route('admin.users.index')
                ->with('error', 'User sistem walk-in tidak dapat dilihat.');
        }

        $user->load('roles');

        return Inertia::render('admin/users/show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user)
    {
        // Prevent editing walk-in system user
        if ($user->email === 'walkin@tokoberas.internal') {
            return redirect()->route('admin.users.index')
                ->with('error', 'User sistem walk-in tidak dapat diedit.');
        }

        $user->load('roles');
        $roles = Role::all();
        \Log::info('Roles for edit user:', $roles->toArray()); // DEBUG

        return Inertia::render('admin/users/edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Prevent updating walk-in system user
        if ($user->email === 'walkin@tokoberas.internal') {
            return redirect()->route('admin.users.index')
                ->with('error', 'User sistem walk-in tidak dapat diupdate.');
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users,username,' . $user->id,
                'phone_number' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
                'role_id' => 'required|exists:roles,id',
            ], [
                'name.required' => 'Nama harus diisi.',
                'username.required' => 'Username harus diisi.',
                'username.unique' => 'Username sudah digunakan.',
                'phone_number.required' => 'Nomor HP harus diisi.',
                'address.required' => 'Alamat harus diisi.',
                'email.required' => 'Email harus diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan.',
                'password.confirmed' => 'Konfirmasi password tidak cocok.',
                'role_id.required' => 'Role harus dipilih.',
                'role_id.exists' => 'Role yang dipilih tidak valid.',
            ]);

            $updateData = [
                'name' => $request->name,
                'username' => $request->username,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'email' => $request->email,
            ];

            if ($request->password) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Update user role
            $user->roles()->sync([$request->role_id]);

            return redirect()->route('admin.users.index')
                ->with('success', 'User berhasil diupdate.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengupdate user: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(User $user)
    {
        try {
            // Prevent deleting the current user
            if ($user->id === auth()->id()) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            }

            // Prevent deleting walk-in system user
            if ($user->email === 'walkin@tokoberas.internal') {
                return redirect()->route('admin.users.index')
                    ->with('error', 'User sistem walk-in tidak dapat dihapus.');
            }

            // Check if user has any sales records
            if ($user->penjualans()->exists()) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'User tidak dapat dihapus karena memiliki riwayat transaksi.');
            }

            // Detach roles before deleting
            $user->roles()->detach();
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'User berhasil dihapus.');
                
        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }
}
