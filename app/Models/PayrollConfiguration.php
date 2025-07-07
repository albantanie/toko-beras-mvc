<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollConfiguration extends Model
{
    protected $fillable = [
        'config_key',
        'config_name',
        'config_type',
        'config_category',
        'applies_to',
        'amount',
        'percentage',
        'min_value',
        'max_value',
        'description',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('config_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('config_category', $category);
    }

    public function scopeAppliesTo($query, $role)
    {
        return $query->where(function($q) use ($role) {
            $q->where('applies_to', 'all')
              ->orWhere('applies_to', $role);
        });
    }

    // Methods
    public function getFormattedAmountAttribute()
    {
        if ($this->amount) {
            return 'Rp ' . number_format($this->amount, 0, ',', '.');
        }
        return null;
    }

    public function getFormattedPercentageAttribute()
    {
        if ($this->percentage) {
            return $this->percentage . '%';
        }
        return null;
    }

    public function getDisplayValueAttribute()
    {
        if ($this->amount) {
            return $this->formatted_amount;
        } elseif ($this->percentage) {
            return $this->formatted_percentage;
        }
        return '-';
    }

    // Static methods for easy configuration retrieval
    public static function getBasicSalary($role)
    {
        $config = self::active()
            ->where('config_category', 'basic')
            ->where('config_key', "basic_salary_{$role}")
            ->first();

        return $config ? $config->amount : 0;
    }

    public static function getOvertimeRate($role = 'all')
    {
        $config = self::active()
            ->where('config_category', 'overtime')
            ->where('config_key', 'overtime_rate')
            ->appliesTo($role)
            ->first();

        return $config ? $config->amount : 0;
    }

    public static function getTaxRate()
    {
        $config = self::active()
            ->where('config_category', 'tax')
            ->where('config_key', 'tax_rate')
            ->first();

        return $config ? $config->percentage : 0;
    }

    public static function getInsuranceRate()
    {
        $config = self::active()
            ->where('config_category', 'insurance')
            ->where('config_key', 'insurance_rate')
            ->first();

        return $config ? $config->percentage : 0;
    }

    public static function getAllowances($role)
    {
        return self::active()
            ->where('config_category', 'allowance')
            ->appliesTo($role)
            ->get();
    }

    public static function getDeductions($role)
    {
        return self::active()
            ->where('config_category', 'deduction')
            ->appliesTo($role)
            ->get();
    }
}
