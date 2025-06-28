<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Barang - Mengelola data produk/barang dalam sistem toko beras
 *
 * Model ini menangani semua operasi terkait produk beras termasuk:
 * - Manajemen stok dan inventori
 * - Perhitungan harga dan profit
 * - Tracking perubahan data oleh user
 * - Validasi stok minimum dan ketersediaan
 *
 * @package App\Models
 */
class Barang extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal
     *
     * Daftar field yang diizinkan untuk mass assignment saat membuat atau mengupdate barang
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'deskripsi',
        'kategori',
        'harga_beli',
        'harga_jual',
        'stok',
        'stok_minimum',
        'satuan',
        'berat_per_unit',
        'kode_barang',
        'gambar',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu
     *
     * Mengkonversi tipe data field tertentu untuk konsistensi dan perhitungan
     */
    protected function casts(): array
    {
        return [
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'berat_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi dengan User yang membuat barang ini
     *
     * Tracking user yang pertama kali menambahkan barang ke sistem
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi dengan User yang terakhir mengupdate barang ini
     *
     * Tracking user yang terakhir melakukan perubahan pada data barang
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relasi one-to-many dengan DetailPenjualan
     *
     * Satu barang dapat muncul di banyak detail penjualan
     */
    public function detailPenjualans(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class);
    }

    /**
     * Mengecek apakah stok barang rendah
     *
     * Membandingkan stok saat ini dengan stok minimum yang ditetapkan
     */
    public function isLowStock(): bool
    {
        return $this->stok <= $this->stok_minimum;
    }

    /**
     * Mengecek apakah barang habis stok
     *
     * Memeriksa apakah stok barang sudah habis atau kurang dari sama dengan 0
     */
    public function isOutOfStock(): bool
    {
        return $this->stok <= 0;
    }

    /**
     * Menghitung margin keuntungan dalam persen
     *
     * Menghitung persentase keuntungan berdasarkan selisih harga jual dan harga beli
     */
    public function getProfitMargin(): float
    {
        if ($this->harga_beli <= 0) {
            return 0;
        }

        return (($this->harga_jual - $this->harga_beli) / $this->harga_beli) * 100;
    }

    /**
     * Menghitung keuntungan per unit
     *
     * Menghitung selisih antara harga jual dan harga beli per unit barang
     */
    public function getProfitPerUnit(): float
    {
        return $this->harga_jual - $this->harga_beli;
    }

    /**
     * Mengurangi stok barang
     *
     * Mengurangi stok barang dengan jumlah tertentu jika stok mencukupi
     */
    public function reduceStock(int $quantity): bool
    {
        if ($this->stok >= $quantity) {
            $this->stok -= $quantity;
            return $this->save();
        }

        return false;
    }

    /**
     * Menambah stok barang
     *
     * Menambahkan stok barang dengan jumlah tertentu
     */
    public function addStock(int $quantity): bool
    {
        $this->stok += $quantity;
        return $this->save();
    }

    /**
     * Scope untuk barang yang aktif
     *
     * Filter query untuk menampilkan hanya barang yang statusnya aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk barang dengan stok rendah
     *
     * Filter query untuk menampilkan barang yang stoknya di bawah atau sama dengan stok minimum
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stok <= stok_minimum');
    }

    /**
     * Scope untuk barang yang habis stok
     *
     * Filter query untuk menampilkan barang yang stoknya habis atau kurang dari sama dengan 0
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stok', '<=', 0);
    }

    /**
     * Scope untuk pencarian barang
     *
     * Filter query untuk mencari barang berdasarkan nama, kode barang, atau kategori
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
              ->orWhere('kode_barang', 'like', "%{$search}%")
              ->orWhere('kategori', 'like', "%{$search}%");
        });
    }
}
