<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $table = 'anggaran';

    protected $fillable = [
        'budget_code',
        'budget_name',
        'budget_type',
        'period',
        'period_start',
        'period_end',
        'category',
        'planned_amount',
        'actual_amount',
        'variance_amount',
        'variance_percentage',
        'status',
        'description',
        'created_by',
        'approved_by',
        'approved_at',
        'breakdown',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'planned_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'approved_at' => 'datetime',
        'breakdown' => 'array',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('budget_type', $type);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Methods
    public function calculateVariance()
    {
        $this->variance_amount = $this->actual_amount - $this->planned_amount;

        if ($this->planned_amount > 0) {
            $this->variance_percentage = ($this->variance_amount / $this->planned_amount) * 100;
        } else {
            $this->variance_percentage = 0;
        }

        $this->save();
    }

    public function updateActualAmount($amount)
    {
        $this->actual_amount = $amount;
        $this->calculateVariance();
    }

    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function activate()
    {
        $this->update(['status' => 'active']);
    }

    public function getFormattedPlannedAmountAttribute()
    {
        return 'Rp ' . number_format($this->planned_amount, 0, ',', '.');
    }

    public function getFormattedActualAmountAttribute()
    {
        return 'Rp ' . number_format($this->actual_amount, 0, ',', '.');
    }

    public function getVarianceStatusAttribute()
    {
        if ($this->variance_amount > 0) {
            return 'over';
        } elseif ($this->variance_amount < 0) {
            return 'under';
        } else {
            return 'on_target';
        }
    }

    public function getVarianceColorAttribute()
    {
        $status = $this->variance_status;

        return match($status) {
            'over' => 'text-red-600',
            'under' => 'text-green-600',
            'on_target' => 'text-blue-600',
            default => 'text-gray-600',
        };
    }
}
