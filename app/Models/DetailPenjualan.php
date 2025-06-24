<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenjualan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'penjualan_id',
        'barang_id',
        'jumlah',
        'harga_satuan',
        'subtotal',
        'catatan',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Get the penjualan that owns this detail.
     */
    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class);
    }

    /**
     * Get the barang for this detail.
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    /**
     * Calculate subtotal automatically
     */
    public function calculateSubtotal(): float
    {
        return $this->jumlah * $this->harga_satuan;
    }

    /**
     * Get profit for this detail
     */
    public function getProfit(): float
    {
        $profitPerUnit = $this->harga_satuan - $this->barang->harga_beli;
        return $profitPerUnit * $this->jumlah;
    }

    /**
     * Boot method to auto-calculate subtotal
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detail) {
            $detail->subtotal = $detail->calculateSubtotal();
        });
    }
}
