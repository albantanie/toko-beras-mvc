<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Get the user's primary role (first role)
     */
    public function getRole(): ?Role
    {
        return $this->roles()->first();
    }

    /**
     * Get the user's role name
     */
    public function getRoleName(): ?string
    {
        $role = $this->getRole();
        return $role ? $role->name : null;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Assign a role to the user
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->hasRole($roleName)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Remove a role from the user
     */
    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    /**
     * Check if user is karyawan
     */
    public function isKaryawan(): bool
    {
        return $this->hasRole(Role::KARYAWAN);
    }

    /**
     * Check if user is kasir
     */
    public function isKasir(): bool
    {
        return $this->hasRole(Role::KASIR);
    }

    /**
     * Check if user is pelanggan
     */
    public function isPelanggan(): bool
    {
        return $this->hasRole(Role::PELANGGAN);
    }

    /**
     * Check if user is owner
     */
    public function isOwner(): bool
    {
        return $this->hasRole(Role::OWNER);
    }

    /**
     * Check if user can manage other users (admin and owner)
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin() || $this->isOwner();
    }

    /**
     * Check if user can manage inventory (admin, owner, karyawan)
     */
    public function canManageInventory(): bool
    {
        return $this->isAdmin() || $this->isOwner() || $this->isKaryawan();
    }

    /**
     * Check if user can process sales (admin, owner, karyawan, kasir)
     */
    public function canProcessSales(): bool
    {
        return $this->isAdmin() || $this->isOwner() || $this->isKaryawan() || $this->isKasir();
    }

    /**
     * Check if user can view reports (admin, owner)
     */
    public function canViewReports(): bool
    {
        return $this->isAdmin() || $this->isOwner();
    }
}
