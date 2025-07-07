<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'payroll_code',
        'user_id',
        'period_month',
        'period_start',
        'period_end',
        'basic_salary',
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'bonus_amount',
        'allowance_amount',
        'deduction_amount',
        'gross_salary',
        'tax_amount',
        'insurance_amount',
        'other_deductions',
        'net_salary',
        'status',
        'payment_date',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'notes',
        'breakdown',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'allowance_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'breakdown' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // Scopes
    public function scopeByPeriod($query, $period)
    {
        return $query->where('period_month', $period);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Methods
    public function calculateGrossSalary()
    {
        $this->gross_salary = $this->basic_salary + $this->overtime_amount + $this->bonus_amount + $this->allowance_amount;
        return $this->gross_salary;
    }

    public function calculateNetSalary()
    {
        $this->net_salary = $this->gross_salary - $this->tax_amount - $this->insurance_amount - $this->deduction_amount - $this->other_deductions;
        return $this->net_salary;
    }

    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid($userId)
    {
        $this->update([
            'status' => 'paid',
            'paid_by' => $userId,
            'paid_at' => now(),
            'payment_date' => now()->toDateString(),
        ]);
    }

    public function getFormattedNetSalaryAttribute()
    {
        return 'Rp ' . number_format($this->net_salary, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'secondary',
            'approved' => 'warning',
            'paid' => 'success',
            'cancelled' => 'danger',
        ];

        return $badges[$this->status] ?? 'secondary';
    }
}
