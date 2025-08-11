<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Penjualan - Mengelola data transaksi penjualan dalam sistem toko beras
 *
 * Model ini menangani semua operasi terkait transaksi penjualan termasuk:
 * - Transaksi offline (walk-in customer) dan online (e-commerce)
 * - Manajemen status pembayaran dan pickup
 * - Generasi nomor transaksi dan kode receipt
 * - Perhitungan total dan keuntungan
 * - Tracking metode pickup dan informasi pelanggan
 *
 * @package App\Models
 */
class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'transaksi';

    /**
     * Atribut yang dapat diisi secara massal
     *
     * Daftar field yang diizinkan untuk mass assignment saat membuat atau mengupdate penjualan
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_transaksi',
        'user_id',
        'pelanggan_id',
        'nama_pelanggan',
        'telepon_pelanggan',
        'alamat_pelanggan',
        'jenis_transaksi',
        'status',
        'metode_pembayaran',
        'payment_proof',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'payment_rejected_at',
        'payment_rejected_by',
        'payment_rejection_reason',
        'pickup_method',
        'pickup_person_name',
        'pickup_person_phone',
        'pickup_notes',
        'pickup_time',
        'receipt_code',
        'subtotal',
        'diskon',
        'pajak',
        'total',
        'bayar',
        'kembalian',
        'total_cost',
        'total_profit',
        'is_financial_recorded',
        'catatan',
        'tanggal_transaksi',
        'financial_account_id',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu
     *
     * Mengkonversi tipe data field tertentu untuk konsistensi perhitungan dan tampilan
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'diskon' => 'decimal:2',
            'pajak' => 'decimal:2',
            'total' => 'decimal:2',
            'bayar' => 'decimal:2',
            'kembalian' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_profit' => 'decimal:2',
            'is_financial_recorded' => 'boolean',
            'tanggal_transaksi' => 'datetime',
        ];
    }

    /**
     * Relasi dengan User yang menangani penjualan ini
     *
     * Mengacu pada kasir, karyawan, atau admin yang memproses transaksi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi dengan User sebagai pelanggan
     *
     * Mengacu pada user dengan role pelanggan yang melakukan pembelian
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelanggan_id');
    }

    /**
     * Relasi one-to-many dengan DetailPenjualan
     *
     * Satu penjualan dapat memiliki banyak detail item yang dibeli
     */
    public function detailPenjualans(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class);
    }

    /**
     * Generate nomor transaksi otomatis
     *
     * Membuat nomor transaksi unik dengan format TRX + tanggal + sequence 4 digit
     */
    public static function generateNomorTransaksi(): string
    {
        $date = now()->format('Ymd');
        $lastTransaction = self::whereDate('created_at', now())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastTransaction ?
            (int) substr($lastTransaction->nomor_transaksi, -4) + 1 : 1;

        return 'TRX' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mengecek apakah transaksi sudah selesai
     *
     * Memeriksa status transaksi apakah sudah dalam status 'selesai'
     */
    public function isCompleted(): bool
    {
        return $this->status === 'selesai';
    }

    /**
     * Mengecek apakah transaksi masih pending
     *
     * Memeriksa status transaksi apakah masih menunggu pembayaran
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mengecek apakah transaksi dibatalkan
     *
     * Memeriksa status transaksi apakah sudah dibatalkan
     */
    public function isCancelled(): bool
    {
        return $this->status === 'dibatalkan';
    }

    /**
     * Mengecek apakah transaksi sudah dibayar (untuk pesanan online)
     *
     * Memeriksa status pembayaran untuk transaksi online
     */
    public function isPaid(): bool
    {
        return $this->status === 'dibayar';
    }

    /**
     * Mendapatkan label status dalam bahasa Indonesia
     *
     * Mengkonversi status kode menjadi label yang mudah dibaca
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu Pembayaran',
            'dibayar' => 'Sudah Dibayar',
            'siap_pickup' => 'Siap Pickup',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get next possible status for online orders
     */
    public function getNextStatus(): ?string
    {
        if ($this->jenis_transaksi !== 'online') {
            return null;
        }

        return match($this->status) {
            'pending' => 'dibayar',
            'dibayar' => 'siap_pickup',
            'siap_pickup' => 'selesai',
            default => null,
        };
    }

    /**
     * Check if status can be updated to next status
     */
    public function canUpdateToNextStatus(): bool
    {
        return $this->getNextStatus() !== null;
    }

    /**
     * Get total items count
     */
    public function getTotalItems(): int
    {
        return $this->detailPenjualans()->sum('jumlah');
    }

    /**
     * Get profit for this sale
     */
    public function getProfit(): float
    {
        $profit = 0;
        foreach ($this->detailPenjualans as $detail) {
            $profitPerUnit = $detail->harga_satuan - $detail->barang->harga_beli;
            $profit += $profitPerUnit * $detail->jumlah;
        }
        return $profit;
    }

    /**
     * Scope for today's sales
     */
    public function scopeToday($query)
    {
        return $query->whereDate('tanggal_transaksi', now());
    }

    /**
     * Scope for this month's sales
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('tanggal_transaksi', now()->month)
                    ->whereYear('tanggal_transaksi', now()->year);
    }

    /**
     * Scope for completed sales
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'selesai');
    }

    /**
     * Scope for online sales
     */
    public function scopeOnline($query)
    {
        return $query->where('jenis_transaksi', 'online');
    }

    /**
     * Scope for offline sales
     */
    public function scopeOffline($query)
    {
        return $query->where('jenis_transaksi', 'offline');
    }

    /**
     * Get formatted transaction date
     */
    public function getFormattedTransactionDateAttribute(): string
    {
        return $this->tanggal_transaksi->setTimezone('Asia/Jakarta')->format('d/m/Y H:i');
    }

    /**
     * Get formatted created date
     */
    public function getFormattedCreatedDateAttribute(): string
    {
        return $this->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i');
    }

    /**
     * Get time ago for transaction
     */
    public function getTransactionTimeAgoAttribute(): string
    {
        return $this->tanggal_transaksi->setTimezone('Asia/Jakarta')->diffForHumans();
    }

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /**
     * Check if this transaction requires a pickup receipt
     * Online transactions require receipt for pickup verification
     */
    public function requiresReceipt(): bool
    {
        return $this->jenis_transaksi === 'online';
    }

    /**
     * Generate unique receipt code for pickup verification
     */
    public function generateReceiptCode(): string
    {
        if ($this->receipt_code) {
            return $this->receipt_code;
        }

        do {
            $code = 'RC' . strtoupper(uniqid());
        } while (self::where('receipt_code', $code)->exists());

        $this->update(['receipt_code' => $code]);

        return $code;
    }

    /**
     * Check if order is ready for pickup
     */
    public function isReadyForPickup(): bool
    {
        return $this->status === 'siap_pickup';
    }

    /**
     * Mark order as picked up (completed)
     */
    public function markAsPickedUp(): void
    {
        $this->update([
            'status' => 'selesai',
        ]);
    }

    /**
     * Get pickup method label in Indonesian
     */
    public function getPickupMethodLabel(): string
    {
        return match($this->pickup_method) {
            'self' => 'Ambil Sendiri',
            'grab' => 'Grab Driver',
            'gojek' => 'Gojek Driver',
            'other' => 'Orang Lain',
            default => ucfirst($this->pickup_method),
        };
    }
}
