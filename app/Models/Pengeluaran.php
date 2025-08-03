<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Pengeluaran - Mengelola data pengeluaran umum dalam sistem toko beras
 *
 * Model ini menangani semua jenis pengeluaran termasuk:
 * - Pengeluaran operasional (listrik, air, internet, dll)
 * - Pengeluaran gaji dan tunjangan
 * - Pengeluaran pembelian barang/supplies
 * - Pengeluaran maintenance dan perbaikan
 * - Pengeluaran marketing dan promosi
 * - Pengeluaran lainnya
 *
 * @package App\Models
 */
class Pengeluaran extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran';

    /**
     * Atribut yang dapat diisi secara massal
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tanggal',
        'keterangan',
        'jumlah',
        'kategori',
        'metode_pembayaran',
        'status',
        'bukti_pembayaran',
        'catatan',
        'created_by',
        'approved_by',
        'approved_at',
        'financial_account_id',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu
     */
    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Konstanta kategori pengeluaran
     */
    public const KATEGORI_OPERASIONAL = 'operasional';
    public const KATEGORI_GAJI = 'gaji';
    public const KATEGORI_PEMBELIAN = 'pembelian';
    public const KATEGORI_MAINTENANCE = 'maintenance';
    public const KATEGORI_MARKETING = 'marketing';
    public const KATEGORI_LAINNYA = 'lainnya';

    /**
     * Konstanta status pengeluaran
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Konstanta metode pembayaran
     */
    public const METODE_TUNAI = 'tunai';
    public const METODE_TRANSFER_BCA = 'transfer_bca';

    /**
     * Mendapatkan semua kategori yang tersedia
     */
    public static function getKategoriOptions(): array
    {
        return [
            self::KATEGORI_OPERASIONAL => 'Operasional',
            self::KATEGORI_GAJI => 'Gaji & Tunjangan',
            self::KATEGORI_PEMBELIAN => 'Pembelian Barang',
            self::KATEGORI_MAINTENANCE => 'Maintenance & Perbaikan',
            self::KATEGORI_MARKETING => 'Marketing & Promosi',
            self::KATEGORI_LAINNYA => 'Lainnya',
        ];
    }

    /**
     * Mendapatkan semua status yang tersedia
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_PAID => 'Sudah Dibayar',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    /**
     * Mendapatkan semua metode pembayaran yang tersedia
     */
    public static function getMetodePembayaranOptions(): array
    {
        return [
            self::METODE_TUNAI => 'Cash',
            self::METODE_TRANSFER_BCA => 'Transfer Bank BCA',
        ];
    }

    /**
     * Relasi dengan User yang membuat pengeluaran
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi dengan User yang menyetujui pengeluaran
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relasi dengan FinancialAccount
     */
    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /**
     * Relasi dengan FinancialTransaction
     */
    public function financialTransaction()
    {
        return $this->hasOne(FinancialTransaction::class, 'reference_id')
            ->where('reference_type', 'pengeluaran');
    }

    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Scope untuk filter berdasarkan user yang membuat
     */
    public function scopeByCreator($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Mendapatkan label kategori
     */
    public function getKategoriLabelAttribute(): string
    {
        $kategoriOptions = self::getKategoriOptions();
        return $kategoriOptions[$this->kategori] ?? $this->kategori;
    }

    /**
     * Mendapatkan label status
     */
    public function getStatusLabelAttribute(): string
    {
        $statusOptions = self::getStatusOptions();
        return $statusOptions[$this->status] ?? $this->status;
    }

    /**
     * Mendapatkan label metode pembayaran
     */
    public function getMetodePembayaranLabelAttribute(): string
    {
        $metodeOptions = self::getMetodePembayaranOptions();
        return $metodeOptions[$this->metode_pembayaran] ?? $this->metode_pembayaran;
    }

    /**
     * Mendapatkan warna status
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_CANCELLED => 'red',
            default => 'gray'
        };
    }

    /**
     * Mengecek apakah pengeluaran sudah disetujui
     */
    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PAID]);
    }

    /**
     * Mengecek apakah pengeluaran sudah dibayar
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Mengecek apakah pengeluaran dapat disetujui
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    /**
     * Mengecek apakah pengeluaran dapat dibayar
     */
    public function canBePaid(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Menyetujui pengeluaran
     */
    public function approve(int $approvedBy, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'catatan' => $notes,
        ]);
    }

    /**
     * Membayar pengeluaran
     */
    public function pay(int $paidBy, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PAID,
            'approved_by' => $paidBy,
            'approved_at' => now(),
            'catatan' => $notes,
        ]);
    }

    /**
     * Membatalkan pengeluaran
     */
    public function cancel(int $cancelledBy, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'approved_by' => $cancelledBy,
            'approved_at' => now(),
            'catatan' => $notes,
        ]);
    }

    /**
     * Membuat financial transaction untuk pengeluaran yang sudah dibayar
     */
    public function createFinancialTransaction(): ?FinancialTransaction
    {
        // Only create transaction for paid expenses
        if (!$this->isPaid() || !$this->financial_account_id) {
            return null;
        }

        // Check if transaction already exists
        if ($this->financialTransaction) {
            return $this->financialTransaction;
        }

        return FinancialTransaction::create([
            'transaction_code' => 'OUT-' . now()->format('YmdHis') . '-' . $this->id,
            'transaction_type' => 'expense',
            'category' => 'expense',
            'subcategory' => $this->kategori,
            'amount' => $this->jumlah,
            'from_account_id' => $this->financial_account_id,
            'to_account_id' => null,
            'reference_type' => 'pengeluaran',
            'reference_id' => $this->id,
            'description' => $this->keterangan,
            'transaction_date' => $this->tanggal,
            'status' => 'completed',
            'created_by' => $this->created_by,
        ]);
    }
}