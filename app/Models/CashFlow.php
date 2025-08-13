<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlow extends Model
{
    protected $table = 'arus_kas';

    protected $fillable = [
        'flow_date',
        'flow_type',
        'direction',
        'category',
        'amount',
        'account_id',
        'transaction_id',
        'description',
        'running_balance',
    ];

    protected $casts = [
        'flow_date' => 'date',
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'transaction_id');
    }

    // Scopes
    public function scopeInflows($query)
    {
        return $query->where('direction', 'inflow');
    }

    public function scopeOutflows($query)
    {
        return $query->where('direction', 'outflow');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('flow_type', $type);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('flow_date', [$startDate, $endDate]);
    }

    // Methods
    public function getFormattedAmountAttribute()
    {
        return \App\Helpers\CurrencyHelper::formatWithDirection($this->amount, $this->direction);
    }

    public function getDirectionColorAttribute()
    {
        return $this->direction === 'inflow' ? 'text-green-600' : 'text-red-600';
    }

    public function getFlowTypeDisplayAttribute()
    {
        // Simplified for rice store - only operating
        return 'Operasional';
    }
}
