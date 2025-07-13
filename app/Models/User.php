<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User - Mengelola data pengguna sistem toko beras
 *
 * Model ini menangani autentikasi pengguna dan sistem role-based access control.
 * Setiap pengguna memiliki satu role yang menentukan hak akses mereka dalam sistem.
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'pengguna';

    /**
     * Atribut yang dapat diisi secara massal
     *
     * Daftar field yang diizinkan untuk mass assignment saat membuat atau mengupdate user
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone_number',
        'address',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi
     *
     * Field-field sensitif yang tidak boleh ditampilkan dalam response JSON
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Mendapatkan atribut yang harus di-cast ke tipe data tertentu
     *
     * Mengkonversi tipe data field tertentu untuk konsistensi
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi many-to-many dengan model Role
     *
     * Satu user dapat memiliki banyak role (meskipun dalam sistem ini dibatasi satu role per user)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'pengguna_peran');
    }

    /**
     * Mendapatkan role utama pengguna (role pertama)
     *
     * Mengambil role pertama dari user karena sistem menggunakan satu role per user
     */
    public function getRole(): ?Role
    {
        return $this->roles()->first();
    }

    /**
     * Mendapatkan nama role pengguna
     *
     * Mengembalikan string nama role atau null jika user tidak memiliki role
     */
    public function getRoleName(): ?string
    {
        $role = $this->getRole();
        return $role ? $role->name : null;
    }

    /**
     * Mengecek apakah user memiliki role tertentu
     *
     * Memeriksa keberadaan role spesifik pada user
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Menambahkan role ke user
     *
     * Memberikan role baru kepada user jika belum memiliki role tersebut
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->hasRole($roleName)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Menghapus role dari user
     *
     * Mencabut role tertentu dari user
     */
    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Mengecek apakah user adalah admin
     *
     * Admin memiliki akses penuh ke sistem termasuk manajemen user dan laporan
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    /**
     * Mengecek apakah user adalah karyawan
     *
     * Karyawan dapat mengelola inventori dan melihat dashboard inventori
     */
    public function isKaryawan(): bool
    {
        return $this->hasRole(Role::KARYAWAN);
    }

    /**
     * Mengecek apakah user adalah kasir
     *
     * Kasir dapat memproses transaksi penjualan dan mengelola pembayaran
     */
    public function isKasir(): bool
    {
        return $this->hasRole(Role::KASIR);
    }

    /**
     * Mengecek apakah user adalah pelanggan
     *
     * Pelanggan dapat melihat katalog produk dan melakukan pembelian
     */
    public function isPelanggan(): bool
    {
        return $this->hasRole(Role::PELANGGAN);
    }

    /**
     * Mengecek apakah user adalah owner
     *
     * Owner memiliki akses lengkap termasuk laporan dan rekomendasi produk
     */
    public function isOwner(): bool
    {
        return $this->hasRole(Role::OWNER);
    }

    /**
     * Mengecek apakah user dapat mengelola user lain
     *
     * Hanya admin dan owner yang dapat membuat, mengubah, dan menghapus user
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin() || $this->isOwner();
    }

    /**
     * Mengecek apakah user dapat mengelola inventori
     *
     * Admin, owner, dan karyawan dapat mengelola stok barang dan produk
     */
    public function canManageInventory(): bool
    {
        return $this->isAdmin() || $this->isOwner() || $this->isKaryawan();
    }

    /**
     * Mengecek apakah user dapat memproses penjualan
     *
     * Admin, owner, karyawan, dan kasir dapat melakukan transaksi penjualan
     */
    public function canProcessSales(): bool
    {
        return $this->isAdmin() || $this->isOwner() || $this->isKaryawan() || $this->isKasir();
    }

    /**
     * Mengecek apakah user dapat melihat laporan
     *
     * Hanya admin dan owner yang dapat mengakses laporan penjualan dan analitik
     */
    public function canViewReports(): bool
    {
        return $this->isAdmin() || $this->isOwner();
    }
}
