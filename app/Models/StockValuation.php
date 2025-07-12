<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockValuation extends Model
{
    protected $fillable = [
        'barang_id',
        'valuation_date',
        'quantity_on_hand',
        'unit_cost',
        'unit_price',
        'total_cost_value',
        'total_market_value',
        'potential_profit',
        'profit_margin_percentage',
        'valuation_method',
        'notes',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'unit_cost' => 'float',
        'unit_price' => 'float',
        'total_cost_value' => 'float',
        'total_market_value' => 'float',
        'potential_profit' => 'float',
        'profit_margin_percentage' => 'float',
    ];

    // Relationships
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('valuation_date', $date);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('valuation_method', $method);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('valuation_date', 'desc');
    }

    // Methods
    public function calculateValues()
    {
        // Validasi: quantity tidak boleh minus
        if ($this->quantity_on_hand < 0) {
            $barangName = $this->barang ? $this->barang->nama : 'Unknown';
            throw new \Exception("Quantity on hand tidak boleh minus untuk barang {$barangName}");
        }

        // Validasi: unit cost dan unit price tidak boleh minus
        if ($this->unit_cost < 0 || $this->unit_price < 0) {
            $barangName = $this->barang ? $this->barang->nama : 'Unknown';
            throw new \Exception("Unit cost dan unit price tidak boleh minus untuk barang {$barangName}");
        }

        $this->total_cost_value = $this->quantity_on_hand * $this->unit_cost;
        $this->total_market_value = $this->quantity_on_hand * $this->unit_price;
        $this->potential_profit = $this->total_market_value - $this->total_cost_value;

        if ($this->total_cost_value > 0) {
            $this->profit_margin_percentage = ($this->potential_profit / $this->total_cost_value) * 100;
        } else {
            $this->profit_margin_percentage = 0;
        }

        $this->save();
    }

    public function getFormattedTotalCostValueAttribute()
    {
        return 'Rp ' . number_format($this->total_cost_value, 0, ',', '.');
    }

    public function getFormattedTotalMarketValueAttribute()
    {
        return 'Rp ' . number_format($this->total_market_value, 0, ',', '.');
    }

    public function getFormattedPotentialProfitAttribute()
    {
        return 'Rp ' . number_format($this->potential_profit, 0, ',', '.');
    }

    public function getProfitStatusAttribute()
    {
        if ($this->potential_profit > 0) {
            return 'profitable';
        } elseif ($this->potential_profit < 0) {
            return 'loss';
        } else {
            return 'break_even';
        }
    }

    public function getProfitColorAttribute()
    {
        $status = $this->profit_status;

        return match($status) {
            'profitable' => 'text-green-600',
            'loss' => 'text-red-600',
            'break_even' => 'text-yellow-600',
            default => 'text-gray-600',
        };
    }

    public function getValuationMethodDisplayAttribute()
    {
        $methods = [
            'fifo' => 'First In First Out (FIFO)',
            'lifo' => 'Last In First Out (LIFO)',
            'average' => 'Weighted Average',
        ];

        return $methods[$this->valuation_method] ?? $this->valuation_method;
    }
}
