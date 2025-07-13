<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'pergerakan_stok';

    protected $fillable = [
        'barang_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_price',
        'description',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
    ];

    /**
     * Get the barang that owns the stock movement
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    /**
     * Get the user who made the stock movement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model (penjualan, pembelian, etc.)
     */
    public function reference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        return $this->reference_type::find($this->reference_id);
    }

    /**
     * Scope untuk filter berdasarkan tipe
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope untuk filter berdasarkan barang
     */
    public function scopeForBarang($query, $barangId)
    {
        return $query->where('barang_id', $barangId);
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'in' => 'Stock Masuk',
            'out' => 'Stock Keluar',
            'adjustment' => 'Penyesuaian Stok',
            'correction' => 'Koreksi Sistem',
            'initial' => 'Stock Awal',
            'return' => 'Retur Barang',
            'damage' => 'Kerusakan',
            'transfer' => 'Transfer',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get type color for UI
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'in', 'return' => 'green',
            'out', 'damage' => 'red',
            'adjustment', 'correction' => 'blue',
            'initial' => 'gray',
            'transfer' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get formatted quantity with sign
     */
    public function getFormattedQuantityAttribute(): string
    {
        $sign = $this->quantity >= 0 ? '+' : '';
        return $sign . number_format($this->quantity);
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        if (!$this->unit_price) {
            return '-';
        }
        return 'Rp ' . number_format($this->unit_price);
    }

    /**
     * Get total value of movement
     */
    public function getTotalValueAttribute(): float
    {
        if (!$this->unit_price) {
            return 0;
        }
        return $this->quantity * $this->unit_price;
    }

    /**
     * Get formatted total value
     */
    public function getFormattedTotalValueAttribute(): string
    {
        if ($this->total_value == 0) {
            return '-';
        }
        return 'Rp ' . number_format($this->total_value);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i');
    }

    /**
     * Get time ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->diffForHumans();
    }

    /**
     * Check if movement is positive (stock increase)
     */
    public function isPositive(): bool
    {
        return in_array($this->type, ['in', 'return', 'adjustment', 'correction', 'initial']);
    }

    /**
     * Check if movement is negative (stock decrease)
     */
    public function isNegative(): bool
    {
        return in_array($this->type, ['out', 'damage']);
    }

    /**
     * Get reference description
     */
    public function getReferenceDescriptionAttribute(): string
    {
        if (!$this->reference_type || !$this->reference_id) {
            return 'Manual';
        }

        return match($this->reference_type) {
            'App\Models\Penjualan' => 'Penjualan #' . $this->reference_id,
            'App\Models\DetailPenjualan' => 'Detail Penjualan #' . $this->reference_id,
            default => $this->reference_type . ' #' . $this->reference_id,
        };
    }
}
