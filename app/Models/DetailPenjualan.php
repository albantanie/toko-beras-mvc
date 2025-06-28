<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DetailPenjualan - Mengelola detail item dalam transaksi penjualan
 *
 * Model ini menyimpan informasi detail setiap item yang dibeli dalam transaksi:
 * - Barang yang dibeli dan jumlahnya
 * - Harga satuan saat transaksi (untuk tracking harga historis)
 * - Perhitungan subtotal otomatis
 * - Perhitungan keuntungan per item
 *
 * @package App\Models
 */
class DetailPenjualan extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal
     *
     * Daftar field yang diizinkan untuk mass assignment saat membuat atau mengupdate detail penjualan
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'penjualan_id',
        'barang_id',
        'jumlah',
        'harga_satuan',
        'subtotal',
        'catatan',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu
     *
     * Mengkonversi tipe data field harga untuk konsistensi perhitungan
     */
    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Relasi dengan model Penjualan
     *
     * Detail penjualan ini milik transaksi penjualan tertentu
     */
    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class);
    }

    /**
     * Relasi dengan model Barang
     *
     * Detail penjualan ini mengacu pada barang tertentu yang dibeli
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    /**
     * Menghitung subtotal secara otomatis
     *
     * Mengkalikan jumlah dengan harga satuan untuk mendapatkan subtotal
     */
    public function calculateSubtotal(): float
    {
        return $this->jumlah * $this->harga_satuan;
    }

    /**
     * Menghitung keuntungan untuk detail ini
     *
     * Menghitung profit berdasarkan selisih harga jual dan harga beli dikali jumlah
     */
    public function getProfit(): float
    {
        $profitPerUnit = $this->harga_satuan - $this->barang->harga_beli;
        return $profitPerUnit * $this->jumlah;
    }

    /**
     * Boot method untuk auto-calculate subtotal
     *
     * Event listener yang otomatis menghitung subtotal sebelum menyimpan data
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detail) {
            $detail->subtotal = $detail->calculateSubtotal();
        });
    }
}
