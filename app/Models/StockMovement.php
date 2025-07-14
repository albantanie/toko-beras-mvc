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
        'unit_cost',
        'total_value',
        'stock_value_in',
        'stock_value_out',
        'stock_value_change',
        'purchase_price',
        'selling_price',
        'description',
        'value_calculation_notes',
        'movement_category',
        'reference_type',
        'reference_id',
        'metadata',
        'is_financial_recorded',
    ];

    protected $casts = [
        'metadata' => 'array',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'stock_value_in' => 'decimal:2',
        'stock_value_out' => 'decimal:2',
        'stock_value_change' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'is_financial_recorded' => 'boolean',
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
     * Calculate and set stock values based on movement type
     */
    public function calculateStockValues(): void
    {
        $quantity = abs($this->quantity);

        switch ($this->type) {
            case 'in':
            case 'return':
            case 'initial':
                // Stock masuk - gunakan harga beli/purchase price
                $price = $this->purchase_price ?? $this->unit_cost ?? $this->barang->harga_beli ?? 0;
                $this->stock_value_in = $quantity * $price;
                $this->stock_value_out = 0;
                $this->stock_value_change = $this->stock_value_in;
                $this->movement_category = 'stock_in';
                $this->value_calculation_notes = "Stock masuk {$quantity} unit @ Rp " . number_format($price) . " = Rp " . number_format($this->stock_value_in);
                break;

            case 'out':
            case 'damage':
                // Stock keluar - gunakan harga jual/selling price untuk penjualan, harga beli untuk kerusakan
                if ($this->type === 'out' && $this->reference_type === 'App\\Models\\Penjualan') {
                    $price = $this->selling_price ?? $this->unit_price ?? $this->barang->harga_jual ?? 0;
                    $this->movement_category = 'sale';
                    $this->value_calculation_notes = "Penjualan {$quantity} unit @ Rp " . number_format($price) . " = Rp " . number_format($quantity * $price);
                } else {
                    $price = $this->unit_cost ?? $this->barang->harga_beli ?? 0;
                    $this->movement_category = 'stock_out';
                    $this->value_calculation_notes = "Stock keluar {$quantity} unit @ Rp " . number_format($price) . " = Rp " . number_format($quantity * $price);
                }
                $this->stock_value_in = 0;
                $this->stock_value_out = $quantity * $price;
                $this->stock_value_change = -$this->stock_value_out;
                break;

            case 'adjustment':
            case 'correction':
                // Penyesuaian - bisa positif atau negatif
                $price = $this->unit_cost ?? $this->barang->harga_beli ?? 0;
                if ($this->quantity > 0) {
                    $this->stock_value_in = $quantity * $price;
                    $this->stock_value_out = 0;
                    $this->stock_value_change = $this->stock_value_in;
                    $this->value_calculation_notes = "Penyesuaian +{$quantity} unit @ Rp " . number_format($price) . " = +Rp " . number_format($this->stock_value_in);
                } else {
                    $this->stock_value_in = 0;
                    $this->stock_value_out = $quantity * $price;
                    $this->stock_value_change = -$this->stock_value_out;
                    $this->value_calculation_notes = "Penyesuaian -{$quantity} unit @ Rp " . number_format($price) . " = -Rp " . number_format($this->stock_value_out);
                }
                $this->movement_category = 'adjustment';
                break;

            default:
                $this->stock_value_in = 0;
                $this->stock_value_out = 0;
                $this->stock_value_change = 0;
                $this->movement_category = 'other';
                $this->value_calculation_notes = "Pergerakan lainnya: {$this->type}";
        }

        // Set total_value untuk backward compatibility
        $this->total_value = abs($this->stock_value_change);
    }

    /**
     * Get formatted stock value change with sign
     */
    public function getFormattedStockValueChangeAttribute(): string
    {
        $value = $this->stock_value_change;
        $sign = $value >= 0 ? '+' : '';
        return $sign . 'Rp ' . number_format(abs($value));
    }

    /**
     * Get movement impact description
     */
    public function getMovementImpactAttribute(): string
    {
        if ($this->stock_value_change > 0) {
            return 'Menambah nilai stock sebesar Rp ' . number_format($this->stock_value_change);
        } elseif ($this->stock_value_change < 0) {
            return 'Mengurangi nilai stock sebesar Rp ' . number_format(abs($this->stock_value_change));
        } else {
            return 'Tidak mempengaruhi nilai stock';
        }
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
