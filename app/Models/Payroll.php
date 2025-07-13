<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $table = 'gaji';

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

    // Status constants untuk flow yang jelas
    const STATUS_DRAFT = 'draft';           // Baru di-generate, belum disetujui
    const STATUS_APPROVED = 'approved';     // Sudah disetujui, siap dibayar
    const STATUS_PAID = 'paid';            // Sudah dibayar, masuk ke pengeluaran
    const STATUS_CANCELLED = 'cancelled';   // Dibatalkan

    public static function getStatusOptions()
    {
        return [
            self::STATUS_DRAFT => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui - Siap Dibayar',
            self::STATUS_PAID => 'Sudah Dibayar',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    public function getStatusLabelAttribute()
    {
        $statuses = self::getStatusOptions();
        return $statuses[$this->status] ?? 'Unknown';
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_CANCELLED => 'red',
            default => 'gray'
        };
    }

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
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid($userId, $financialTransactionId = null)
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_by' => $userId,
            'paid_at' => now(),
            'payment_date' => now()->toDateString(),
            'financial_transaction_id' => $financialTransactionId, // Link ke transaksi keuangan
        ]);
    }

    public function cancel($userId, $reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    // Helper methods untuk status checking
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeApproved()
    {
        return $this->isDraft();
    }

    public function canBePaid()
    {
        return $this->isApproved();
    }

    public function canBeCancelled()
    {
        return $this->isDraft() || $this->isApproved();
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
