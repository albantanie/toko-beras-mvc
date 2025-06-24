<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the users that belong to the role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    /**
     * Role constants for easy reference
     */
    public const ADMIN = 'admin';
    public const KARYAWAN = 'karyawan';
    public const KASIR = 'kasir';
    public const PELANGGAN = 'pelanggan';
    public const OWNER = 'owner';

    /**
     * Get all available roles
     */
    public static function getAllRoles(): array
    {
        return [
            self::ADMIN,
            self::OWNER,
            self::KARYAWAN,
            self::KASIR,
            self::PELANGGAN,
        ];
    }

    /**
     * Check if role is admin
     */
    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    /**
     * Check if role is karyawan
     */
    public function isKaryawan(): bool
    {
        return $this->name === self::KARYAWAN;
    }

    /**
     * Check if role is kasir
     */
    public function isKasir(): bool
    {
        return $this->name === self::KASIR;
    }

    /**
     * Check if role is pelanggan
     */
    public function isPelanggan(): bool
    {
        return $this->name === self::PELANGGAN;
    }

    /**
     * Check if role is owner
     */
    public function isOwner(): bool
    {
        return $this->name === self::OWNER;
    }
}
