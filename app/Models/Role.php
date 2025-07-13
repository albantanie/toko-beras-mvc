<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model Role - Mengelola data role/peran pengguna dalam sistem toko beras
 *
 * Model ini mendefinisikan berbagai peran yang dapat dimiliki pengguna:
 * - Admin: Akses penuh sistem, manajemen user, laporan
 * - Owner: Akses lengkap, laporan, rekomendasi produk
 * - Karyawan: Manajemen inventori, dashboard inventori
 * - Kasir: Transaksi penjualan, pembayaran
 * - Pelanggan: Katalog produk, pembelian
 *
 * @package App\Models
 */
class Role extends Model
{
    use HasFactory;

    protected $table = 'peran';

    /**
     * Atribut yang dapat diisi secara massal
     *
     * Daftar field yang diizinkan untuk mass assignment saat membuat atau mengupdate role
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relasi many-to-many dengan model User
     *
     * Satu role dapat dimiliki oleh banyak user
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pengguna_peran');
    }

    /**
     * Konstanta role untuk referensi mudah
     *
     * Mendefinisikan nama-nama role yang tersedia dalam sistem
     */
    public const ADMIN = 'admin';
    public const KARYAWAN = 'karyawan';
    public const KASIR = 'kasir';
    public const PELANGGAN = 'pelanggan';
    public const OWNER = 'owner';

    /**
     * Mendapatkan semua role yang tersedia
     *
     * Mengembalikan array berisi semua nama role dalam sistem
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
     * Mengecek apakah role adalah admin
     *
     * Admin memiliki akses penuh ke sistem termasuk manajemen user dan laporan
     */
    public function isAdmin(): bool
    {
        return strtolower($this->name) === self::ADMIN;
    }

    /**
     * Mengecek apakah role adalah karyawan
     *
     * Karyawan dapat mengelola inventori dan melihat dashboard inventori
     */
    public function isKaryawan(): bool
    {
        return strtolower($this->name) === self::KARYAWAN;
    }

    /**
     * Mengecek apakah role adalah kasir
     *
     * Kasir dapat memproses transaksi penjualan dan mengelola pembayaran
     */
    public function isKasir(): bool
    {
        return strtolower($this->name) === self::KASIR;
    }

    /**
     * Mengecek apakah role adalah pelanggan
     *
     * Pelanggan dapat melihat katalog produk dan melakukan pembelian
     */
    public function isPelanggan(): bool
    {
        return strtolower($this->name) === self::PELANGGAN;
    }

    /**
     * Mengecek apakah role adalah owner
     *
     * Owner memiliki akses lengkap termasuk laporan dan rekomendasi produk
     */
    public function isOwner(): bool
    {
        return strtolower($this->name) === self::OWNER;
    }
}
