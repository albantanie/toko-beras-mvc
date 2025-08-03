<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    protected $table = 'akun_keuangan';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'account_category',
        'opening_balance',
        'current_balance',
        'bank_name',
        'account_number',
        'description',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function transactionsFrom(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'from_account_id');
    }

    public function transactionsTo(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'to_account_id');
    }

    // Get all transactions for this account (query builder method)
    public function getAllTransactions()
    {
        // Return transactions where this account is either from or to account
        return FinancialTransaction::where('from_account_id', $this->id)
            ->orWhere('to_account_id', $this->id);
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class, 'account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    // Methods
    public function updateBalance($amount, $operation = 'add')
    {
        if ($operation === 'add') {
            $this->current_balance += $amount;
        } else {
            $this->current_balance -= $amount;
        }
        $this->save();
    }

    public function getFormattedBalanceAttribute()
    {
        return 'Rp ' . number_format($this->current_balance, 0, ',', '.');
    }
}
