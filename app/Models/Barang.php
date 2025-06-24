<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
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
     * The attributes that should be cast.
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
     * Get the user who created this barang.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this barang.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the detail penjualans for this barang.
     */
    public function detailPenjualans(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class);
    }

    /**
     * Check if barang is low stock
     */
    public function isLowStock(): bool
    {
        return $this->stok <= $this->stok_minimum;
    }

    /**
     * Check if barang is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stok <= 0;
    }

    /**
     * Get profit margin
     */
    public function getProfitMargin(): float
    {
        if ($this->harga_beli <= 0) {
            return 0;
        }

        return (($this->harga_jual - $this->harga_beli) / $this->harga_beli) * 100;
    }

    /**
     * Get profit per unit
     */
    public function getProfitPerUnit(): float
    {
        return $this->harga_jual - $this->harga_beli;
    }

    /**
     * Reduce stock
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
     * Add stock
     */
    public function addStock(int $quantity): bool
    {
        $this->stok += $quantity;
        return $this->save();
    }

    /**
     * Scope for active barangs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for low stock barangs
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stok <= stok_minimum');
    }

    /**
     * Scope for out of stock barangs
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stok', '<=', 0);
    }

    /**
     * Scope for search
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
