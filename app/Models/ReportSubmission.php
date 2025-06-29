<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Model ReportSubmission - Mengelola laporan dengan dual crosscheck system
 *
 * Workflow:
 * 1. Kasir/Karyawan submit laporan (draft)
 * 2. Submit untuk crosscheck (submitted)
 * 3. Karyawan/Kasir crosscheck (crosscheck_pending -> crosscheck_approved/rejected)
 * 4. Owner approve/reject (owner_pending -> approved/rejected)
 *
 * @package App\Models
 */
class ReportSubmission extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal
     */
    protected $fillable = [
        'title',
        'type',
        'data',
        'summary',
        'period_from',
        'period_to',
        'status',
        'submitted_by',
        'crosscheck_by',
        'approved_by',
        'submitted_at',
        'crosschecked_at',
        'approved_at',
        'submission_notes',
        'crosscheck_notes',
        'approval_notes',
        'file_path',
        'crosscheck_file_path',
        'pdf_paths',
    ];

    /**
     * Casting attributes ke tipe data yang sesuai
     */
    protected $casts = [
        'data' => 'array',
        'pdf_paths' => 'array',
        'period_from' => 'date',
        'period_to' => 'date',
        'submitted_at' => 'datetime',
        'crosschecked_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Konstanta untuk status laporan
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CROSSCHECK_PENDING = 'crosscheck_pending';
    public const STATUS_CROSSCHECK_APPROVED = 'crosscheck_approved';
    public const STATUS_CROSSCHECK_REJECTED = 'crosscheck_rejected';
    public const STATUS_OWNER_PENDING = 'owner_pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Konstanta untuk jenis laporan
     */
    public const TYPE_PENJUALAN_SUMMARY = 'penjualan_summary';
    public const TYPE_PENJUALAN_DETAIL = 'penjualan_detail';
    public const TYPE_TRANSAKSI_HARIAN = 'transaksi_harian';
    public const TYPE_TRANSAKSI_KASIR = 'transaksi_kasir';
    public const TYPE_BARANG_STOK = 'barang_stok';
    public const TYPE_BARANG_MOVEMENT = 'barang_movement';
    public const TYPE_BARANG_PERFORMANCE = 'barang_performance';
    public const TYPE_KEUANGAN = 'keuangan';
    public const TYPE_DUAL_CHECK = 'dual_check';

    /**
     * Relasi dengan User (yang submit laporan)
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Relasi dengan User (yang crosscheck)
     */
    public function crosschecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'crosscheck_by');
    }

    /**
     * Relasi dengan User (owner yang approve)
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope untuk laporan yang pending crosscheck
     */
    public function scopePendingCrosscheck($query)
    {
        return $query->where('status', self::STATUS_CROSSCHECK_PENDING);
    }

    /**
     * Scope untuk laporan yang pending approval owner
     */
    public function scopePendingOwnerApproval($query)
    {
        return $query->where('status', self::STATUS_OWNER_PENDING);
    }

    /**
     * Scope untuk laporan yang sudah approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope untuk laporan berdasarkan jenis
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Submit laporan untuk crosscheck
     */
    public function submitForCrosscheck(?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_CROSSCHECK_PENDING,
            'submitted_at' => now(),
            'submission_notes' => $notes,
        ]);
    }

    /**
     * Crosscheck approve laporan
     */
    public function crosscheckApprove(int $crosscheckerId, ?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_CROSSCHECK_PENDING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_OWNER_PENDING,
            'crosscheck_by' => $crosscheckerId,
            'crosschecked_at' => now(),
            'crosscheck_notes' => $notes,
        ]);
    }

    /**
     * Crosscheck reject laporan
     */
    public function crosscheckReject(int $crosscheckerId, string $notes): bool
    {
        if ($this->status !== self::STATUS_CROSSCHECK_PENDING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_CROSSCHECK_REJECTED,
            'crosscheck_by' => $crosscheckerId,
            'crosschecked_at' => now(),
            'crosscheck_notes' => $notes,
        ]);
    }

    /**
     * Owner approve laporan
     */
    public function approve(int $approverId, ?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_OWNER_PENDING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Owner reject laporan
     */
    public function reject(int $approverId, string $notes): bool
    {
        if ($this->status !== self::STATUS_OWNER_PENDING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Check apakah laporan bisa di-edit
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT, 
            self::STATUS_CROSSCHECK_REJECTED, 
            self::STATUS_REJECTED
        ]);
    }

    /**
     * Check apakah laporan bisa di-crosscheck
     */
    public function canCrosscheck(): bool
    {
        return $this->status === self::STATUS_CROSSCHECK_PENDING;
    }

    /**
     * Check apakah laporan bisa di-approve oleh owner
     */
    public function canApprove(): bool
    {
        return $this->status === self::STATUS_OWNER_PENDING;
    }

    /**
     * Check apakah user bisa crosscheck laporan ini
     */
    public function canBeCrosscheckedBy(User $user): bool
    {
        // User tidak bisa crosscheck laporan sendiri
        if ($this->submitted_by === $user->id) {
            return false;
        }

        // Hanya kasir dan karyawan yang bisa crosscheck
        if (!$user->isKasir() && !$user->isKaryawan()) {
            return false;
        }

        // Submitter dan crosschecker harus berbeda role
        $submitter = $this->submitter;
        if ($submitter->isKasir() && $user->isKasir()) {
            return false; // Kasir tidak bisa crosscheck laporan kasir lain
        }
        if ($submitter->isKaryawan() && $user->isKaryawan()) {
            return false; // Karyawan tidak bisa crosscheck laporan karyawan lain
        }

        return $this->canCrosscheck();
    }

    /**
     * Get status label dalam bahasa Indonesia
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Sudah Disubmit',
            self::STATUS_CROSSCHECK_PENDING => 'Menunggu Crosscheck',
            self::STATUS_CROSSCHECK_APPROVED => 'Crosscheck Disetujui',
            self::STATUS_CROSSCHECK_REJECTED => 'Crosscheck Ditolak',
            self::STATUS_OWNER_PENDING => 'Menunggu Approval Owner',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get type label dalam bahasa Indonesia
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_PENJUALAN_SUMMARY => 'Laporan Ringkasan Penjualan',
            self::TYPE_PENJUALAN_DETAIL => 'Laporan Detail Penjualan',
            self::TYPE_TRANSAKSI_HARIAN => 'Laporan Transaksi Harian',
            self::TYPE_TRANSAKSI_KASIR => 'Laporan Performa Kasir',
            self::TYPE_BARANG_STOK => 'Laporan Stok Barang',
            self::TYPE_BARANG_MOVEMENT => 'Laporan Pergerakan Barang',
            self::TYPE_BARANG_PERFORMANCE => 'Laporan Performa Barang',
            self::TYPE_KEUANGAN => 'Laporan Keuangan',
            self::TYPE_DUAL_CHECK => 'Laporan Dual Check',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get status color untuk UI
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SUBMITTED => 'blue',
            self::STATUS_CROSSCHECK_PENDING => 'yellow',
            self::STATUS_CROSSCHECK_APPROVED => 'green',
            self::STATUS_CROSSCHECK_REJECTED => 'red',
            self::STATUS_OWNER_PENDING => 'orange',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            default => 'gray',
        };
    }

    /**
     * Generate laporan ringkasan penjualan
     */
    public static function generatePenjualanSummaryReport(
        string $periodFrom,
        string $periodTo,
        int $submittedBy
    ): self {
        // Logic untuk generate laporan penjualan
        $data = [
            'period' => [
                'from' => $periodFrom,
                'to' => $periodTo,
            ],
            'summary' => [
                'total_transactions' => 0,
                'total_revenue' => 0,
                'total_items_sold' => 0,
            ],
            'transactions' => [],
        ];

        return self::create([
            'title' => "Laporan Ringkasan Penjualan {$periodFrom} - {$periodTo}",
            'type' => self::TYPE_PENJUALAN_SUMMARY,
            'data' => $data,
            'summary' => 'Laporan ringkasan penjualan periode ' . $periodFrom . ' - ' . $periodTo,
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'submitted_by' => $submittedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan stok barang
     */
    public static function generateBarangStokReport(int $submittedBy): self
    {
        // Logic untuk generate laporan stok
        $data = [
            'summary' => [
                'total_items' => 0,
                'low_stock_items' => 0,
                'out_of_stock_items' => 0,
                'total_value' => 0,
            ],
            'items' => [],
        ];

        return self::create([
            'title' => 'Laporan Stok Barang ' . now()->format('d M Y'),
            'type' => self::TYPE_BARANG_STOK,
            'data' => $data,
            'summary' => 'Laporan stok barang terkini',
            'period_from' => now()->format('Y-m-d'),
            'period_to' => now()->format('Y-m-d'),
            'submitted_by' => $submittedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Set PDF path untuk laporan
     */
    public function setPdfPath(string $type, string $path): bool
    {
        $pdfPaths = $this->pdf_paths ?? [];
        $pdfPaths[$type] = $path;
        
        return $this->update(['pdf_paths' => $pdfPaths]);
    }

    /**
     * Get PDF path untuk laporan tertentu
     */
    public function getPdfPath(string $type): ?string
    {
        return $this->pdf_paths[$type] ?? null;
    }

    /**
     * Get semua PDF paths
     */
    public function getAllPdfPaths(): array
    {
        return $this->pdf_paths ?? [];
    }

    /**
     * Check apakah laporan memiliki PDF
     */
    public function hasPdf(string $type): bool
    {
        return isset($this->pdf_paths[$type]);
    }

    /**
     * Get crosschecker yang sesuai berdasarkan submitter
     */
    public function getEligibleCrosscheckers(): \Illuminate\Database\Eloquent\Collection
    {
        $submitter = $this->submitter;
        
        if ($submitter->isKasir()) {
            // Jika submitter kasir, crosschecker harus karyawan
            return User::whereHas('roles', function ($query) {
                $query->where('name', 'karyawan');
            })->get();
        } else {
            // Jika submitter karyawan, crosschecker harus kasir
            return User::whereHas('roles', function ($query) {
                $query->where('name', 'kasir');
            })->get();
        }
    }

    /**
     * Check apakah user bisa menjadi crosschecker untuk laporan ini
     */
    public function canBeCrosscheckedByUser(User $user): bool
    {
        $submitter = $this->submitter;
        
        // User tidak bisa crosscheck laporan sendiri
        if ($this->submitted_by === $user->id) {
            return false;
        }

        // Submitter dan crosschecker harus berbeda role
        if ($submitter->isKasir() && $user->isKasir()) {
            return false;
        }
        if ($submitter->isKaryawan() && $user->isKaryawan()) {
            return false;
        }

        return $this->canCrosscheck();
    }
}
