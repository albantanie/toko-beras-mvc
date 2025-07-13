<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $table = 'detail_pembelian';

    protected $fillable = [
        'purchase_id',
        'barang_id',
        'quantity',
        'unit_cost',
        'unit_price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relationships
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    // Methods
    public function calculateSubtotal()
    {
        $this->subtotal = $this->quantity * $this->unit_cost;
        return $this->subtotal;
    }

    public function calculateMargin()
    {
        if ($this->unit_price && $this->unit_cost > 0) {
            return (($this->unit_price - $this->unit_cost) / $this->unit_cost) * 100;
        }
        return 0;
    }

    // Boot method to auto-calculate subtotal
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detail) {
            $detail->calculateSubtotal();
        });
    }
}
