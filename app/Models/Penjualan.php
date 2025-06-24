<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penjualan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
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
        'pickup_method',
        'pickup_person_name',
        'pickup_person_phone',
        'pickup_notes',
        'pickup_time',
        'receipt_code',
        'status',
        'metode_pembayaran',
        'subtotal',
        'diskon',
        'pajak',
        'total',
        'bayar',
        'kembalian',
        'catatan',
        'tanggal_transaksi',
    ];

    /**
     * The attributes that should be cast.
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
            'tanggal_transaksi' => 'datetime',
        ];
    }

    /**
     * Get the user (kasir/karyawan) who handled this sale.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pelanggan for this sale.
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelanggan_id');
    }

    /**
     * Get the detail penjualans for this sale.
     */
    public function detailPenjualans(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class);
    }

    /**
     * Generate nomor transaksi
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
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'selesai';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'dibatalkan';
    }

    /**
     * Check if transaction is paid (for online orders)
     */
    public function isPaid(): bool
    {
        return $this->status === 'dibayar';
    }

    /**
     * Check if transaction is ready for pickup
     */
    public function isReadyPickup(): bool
    {
        return $this->status === 'siap_pickup';
    }

    /**
     * Get status label in Indonesian
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

    /**
     * Check if pickup requires receipt
     */
    public function requiresReceipt(): bool
    {
        return in_array($this->pickup_method, ['grab', 'gojek', 'other']);
    }

    /**
     * Generate receipt code
     */
    public function generateReceiptCode(): string
    {
        if ($this->receipt_code) {
            return $this->receipt_code;
        }

        $code = 'RC' . date('Ymd') . str_pad($this->id, 4, '0', STR_PAD_LEFT);
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
     * Mark as picked up
     */
    public function markAsPickedUp(): void
    {
        $this->update([
            'status' => 'selesai',
            'pickup_time' => now(),
        ]);
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
}
