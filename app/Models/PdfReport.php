<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Model PdfReport - Manages PDF-based financial and stock reports with approval workflow
 *
 * This model handles:
 * - PDF report generation and storage
 * - Approval workflow (pending -> approved/rejected)
 * - File management for PDF reports
 * - Access control (OWNER role only)
 *
 * @package App\Models
 */
class PdfReport extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'pdf_reports';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'type',
        'report_date',
        'period_from',
        'period_to',
        'file_name',
        'file_path',
        'status',
        'generated_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'report_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'report_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'approved_at' => 'datetime',
        'report_data' => 'array',
    ];

    /**
     * Report type constants
     */
    public const TYPE_FINANCIAL = 'financial';
    public const TYPE_STOCK = 'stock';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Relationship with User (who generated the report)
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Relationship with User (who approved/rejected the report)
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for pending reports
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved reports
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected reports
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for financial reports
     */
    public function scopeFinancial($query)
    {
        return $query->where('type', self::TYPE_FINANCIAL);
    }

    /**
     * Scope for stock reports
     */
    public function scopeStock($query)
    {
        return $query->where('type', self::TYPE_STOCK);
    }

    /**
     * Approve the report
     */
    public function approve(int $approvedBy, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Reject the report
     */
    public function reject(int $rejectedBy, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Check if report is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if report is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if report is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get the full storage path for the PDF file
     */
    public function getFullPath(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Check if the PDF file exists
     */
    public function fileExists(): bool
    {
        return file_exists($this->getFullPath());
    }

    /**
     * Generate a date-based filename for the report
     */
    public static function generateFileName(string $type, Carbon $date, ?string $periodFrom = null, ?string $periodTo = null): string
    {
        $dateStr = $date->format('Y-m-d');

        if ($type === self::TYPE_FINANCIAL && $periodFrom && $periodTo) {
            return "financial_report_{$periodFrom}_to_{$periodTo}_{$dateStr}.pdf";
        } elseif ($type === self::TYPE_STOCK) {
            return "stock_report_{$dateStr}.pdf";
        }

        return "{$type}_report_{$dateStr}.pdf";
    }
}
