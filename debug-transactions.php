<?php

// Debug script untuk memeriksa transaksi di database
// Jalankan dengan: php debug-transactions.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Penjualan;

echo "=== DEBUG TRANSAKSI TERBARU ===\n\n";

// Ambil 10 transaksi terbaru
$transactions = Penjualan::with(['user', 'detailPenjualans.barang'])
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

if ($transactions->isEmpty()) {
    echo "❌ Tidak ada transaksi ditemukan di database\n";
    exit;
}

echo "✅ Ditemukan {$transactions->count()} transaksi terbaru:\n\n";

foreach ($transactions as $index => $transaction) {
    echo "--- Transaksi #" . ($index + 1) . " ---\n";
    echo "ID: {$transaction->id}\n";
    echo "Nomor: {$transaction->nomor_transaksi}\n";
    echo "Pelanggan: {$transaction->nama_pelanggan}\n";
    echo "Status: {$transaction->status}\n";
    echo "Metode Pembayaran: {$transaction->metode_pembayaran}\n";
    echo "Total: Rp " . number_format($transaction->total, 0, ',', '.') . "\n";
    echo "Kasir: " . ($transaction->user ? $transaction->user->name : 'N/A') . "\n";
    echo "Tanggal: {$transaction->tanggal_transaksi}\n";
    echo "Created: {$transaction->created_at}\n";
    
    if ($transaction->detailPenjualans->count() > 0) {
        echo "Items:\n";
        foreach ($transaction->detailPenjualans as $detail) {
            echo "  - {$detail->barang->nama}: {$detail->jumlah} x Rp " . number_format($detail->harga_satuan, 0, ',', '.') . "\n";
        }
    }
    echo "\n";
}

echo "=== STATISTIK ===\n";
echo "Total transaksi hari ini: " . Penjualan::whereDate('tanggal_transaksi', today())->count() . "\n";
echo "Total transaksi selesai: " . Penjualan::where('status', 'selesai')->count() . "\n";
echo "Total transaksi pending: " . Penjualan::where('status', 'pending')->count() . "\n";
echo "Total transaksi dibatalkan: " . Penjualan::where('status', 'dibatalkan')->count() . "\n";

echo "\n=== METODE PEMBAYARAN ===\n";
$paymentMethods = Penjualan::selectRaw('metode_pembayaran, COUNT(*) as count')
    ->groupBy('metode_pembayaran')
    ->get();

foreach ($paymentMethods as $method) {
    echo "{$method->metode_pembayaran}: {$method->count} transaksi\n";
}

echo "\n=== SELESAI ===\n";
