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

    protected $table = 'produk';

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
        'harga_pokok',
        'margin_percentage',
        'stok',
        'stok_minimum',
        'minimum_stock_value',
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
            'harga_pokok' => 'decimal:2',
            'margin_percentage' => 'decimal:2',
            'minimum_stock_value' => 'decimal:2',
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

    /**
     * Filter data barang untuk kasir (hide harga_beli)
     *
     * Mengembalikan data barang tanpa informasi sensitif seperti harga beli
     * untuk mencegah kasir mengetahui margin keuntungan
     */
    public function toArrayForKasir(): array
    {
        $data = $this->toArray();
        
        // Remove sensitive fields for kasir
        unset($data['harga_beli']);
        
        // Also remove profit-related computed values
        $data['profit_margin'] = null;
        $data['profit_per_unit'] = null;
        
        return $data;
    }

    /**
     * Filter collection untuk kasir
     *
     * Memfilter collection barang untuk menghilangkan data sensitif
     */
    public static function filterForKasir($barangs)
    {
        if ($barangs instanceof \Illuminate\Database\Eloquent\Collection) {
            return $barangs->map(function ($barang) {
                return $barang->toArrayForKasir();
            });
        }
        
        if (is_array($barangs)) {
            return array_map(function ($barang) {
                if (is_object($barang) && method_exists($barang, 'toArrayForKasir')) {
                    return $barang->toArrayForKasir();
                }
                return $barang;
            }, $barangs);
        }
        
        return $barangs;
    }

    /**
     * Get the stock movements for this barang
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get recent stock movements
     */
    public function recentStockMovements($limit = 10)
    {
        return $this->stockMovements()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    /**
     * Record stock movement
     */
    public function recordStockMovement($type, $quantity, $description, $userId = null, $referenceType = null, $referenceId = null, $unitPrice = null, $metadata = [])
    {
        $userId = $userId ?? auth()->id() ?? 1; // Fallback to user ID 1 (system)
        $stockBefore = $this->stok;

        // Calculate new stock based on type
        $newStock = match($type) {
            'in', 'return', 'adjustment', 'correction', 'initial' => $stockBefore + $quantity,
            'out', 'damage' => $stockBefore - abs($quantity),
            'transfer' => $stockBefore + $quantity, // transfer bisa positif/negatif
            default => $stockBefore,
        };

        // Validasi: Stock tidak boleh minus
        if ($newStock < 0) {
            throw new \Exception("Stock tidak mencukupi. Stock saat ini: {$stockBefore}, diminta: " . abs($quantity) . " untuk {$this->nama}");
        }

        // Create stock movement record
        $movement = $this->stockMovements()->create([
            'user_id' => $userId,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $newStock,
            'unit_price' => $unitPrice,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => $metadata,
        ]);

        // Update barang stock
        $this->update(['stok' => $newStock]);

        return $movement;
    }

    /**
     * Get stock movement summary
     */
    public function getStockMovementSummary($startDate = null, $endDate = null)
    {
        $query = $this->stockMovements();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return [
            'total_in' => $query->clone()->ofType('in')->sum('quantity'),
            'total_out' => $query->clone()->ofType('out')->sum('quantity'),
            'total_adjustment' => $query->clone()->ofType('adjustment')->sum('quantity'),
            'total_return' => $query->clone()->ofType('return')->sum('quantity'),
            'total_damage' => $query->clone()->ofType('damage')->sum('quantity'),
            'movements_count' => $query->clone()->count(),
        ];
    }

    /**
     * Get stock movement chart data
     */
    public function getStockMovementChartData($days = 30)
    {
        $startDate = now()->subDays($days);
        
        return $this->stockMovements()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(CASE WHEN type IN ("in", "return", "adjustment", "correction", "initial") THEN quantity ELSE -quantity END) as net_change')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'net_change' => $item->net_change,
                ];
            });
    }

    /**
     * Get formatted created date
     */
    public function getFormattedCreatedDateAttribute(): string
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i');
    }

    /**
     * Get formatted updated date
     */
    public function getFormattedUpdatedDateAttribute(): string
    {
        return $this->updated_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i');
    }

    /**
     * Get time ago for created date
     */
    public function getCreatedTimeAgoAttribute(): string
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->diffForHumans();
    }

    /**
     * Get unit for rice products
     * Semua produk beras menggunakan satuan kg
     */
    public function getSatuanAttribute(): string
    {
        return 'kg'; // Semua produk beras menggunakan kg
    }
}
