<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\StockMovement;
use App\Models\DailyReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk test generate laporan bulanan
 * Membuat data untuk bulan Juni 2025 secara lengkap
 */
class TestMonthlyReportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting Test Monthly Report Seeder for June 2025...');
        
        // Get users
        $kasir = User::whereHas('roles', function($q) {
            $q->where('name', 'kasir');
        })->first();
        
        $karyawan = User::whereHas('roles', function($q) {
            $q->where('name', 'karyawan');
        })->first();
        
        if (!$kasir || !$karyawan) {
            $this->command->error('Kasir or Karyawan user not found!');
            return;
        }
        
        // Get barangs
        $barangs = Barang::where('is_active', true)->get();
        if ($barangs->isEmpty()) {
            $this->command->error('No active barangs found!');
            return;
        }
        
        // Generate data for June 2025
        $monthStart = Carbon::create(2025, 6, 1)->startOfMonth();
        $monthEnd = Carbon::create(2025, 6, 30)->endOfMonth();
        
        $this->command->info("📅 Generating data for {$monthStart->format('F Y')}");
        
        // Clear existing data for June 2025
        $this->clearExistingData($monthStart, $monthEnd, $kasir->id, $karyawan->id);
        
        // Generate transactions and stock movements for each day
        $currentDate = $monthStart->copy();
        while ($currentDate <= $monthEnd) {
            // Skip future dates
            if ($currentDate > Carbon::now()) {
                $currentDate->addDay();
                continue;
            }
            
            $this->command->info("📊 Generating data for {$currentDate->format('d/m/Y')}");
            
            // Generate 5-10 transactions per day
            $transactionsPerDay = rand(5, 10);
            for ($i = 0; $i < $transactionsPerDay; $i++) {
                $this->createTransaction($currentDate, $kasir, $barangs);
            }
            
            // Generate stock movements (2-3 per day)
            $movementsPerDay = rand(2, 3);
            for ($i = 0; $i < $movementsPerDay; $i++) {
                $this->createStockMovement($currentDate, $karyawan, $barangs);
            }
            
            $currentDate->addDay();
        }
        
        // Generate daily reports for each day
        $currentDate = $monthStart->copy();
        while ($currentDate <= $monthEnd) {
            // Skip future dates
            if ($currentDate > Carbon::now()) {
                $currentDate->addDay();
                continue;
            }
            
            // Generate daily transaction report
            DailyReport::generateTransactionReport($currentDate, $kasir->id);
            
            // Generate daily stock report
            DailyReport::generateStockReport($currentDate, $karyawan->id);
            
            $currentDate->addDay();
        }
        
        $this->command->info('✅ Test Monthly Report Seeder completed successfully!');
        $this->command->info("📋 Generated data for {$monthStart->format('F Y')}");
        $this->command->info("👤 Kasir: {$kasir->name}");
        $this->command->info("👤 Karyawan: {$karyawan->name}");
    }
    
    private function clearExistingData(Carbon $monthStart, Carbon $monthEnd, int $kasirId, int $karyawanId): void
    {
        $this->command->info('🧹 Clearing existing data for June 2025...');
        
        // Delete daily reports
        DailyReport::whereBetween('report_date', [$monthStart, $monthEnd])
            ->whereIn('user_id', [$kasirId, $karyawanId])
            ->delete();
        
        // Delete transactions and their details
        $penjualans = Penjualan::whereBetween('tanggal_transaksi', [$monthStart, $monthEnd])
            ->where('user_id', $kasirId)
            ->get();
        
        foreach ($penjualans as $penjualan) {
            $penjualan->detailPenjualans()->delete();
            $penjualan->delete();
        }
        
        // Delete stock movements
        StockMovement::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('user_id', $karyawanId)
            ->delete();
    }
    
    private function createTransaction(Carbon $date, User $kasir, $barangs): void
    {
        // Random time during business hours
        $randomTime = $date->copy()
            ->setHour(rand(8, 20))
            ->setMinute(rand(0, 59))
            ->setSecond(rand(0, 59));
        
        // Generate unique transaction number
        do {
            $nomorTransaksi = 'TRX-' . $randomTime->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Penjualan::where('nomor_transaksi', $nomorTransaksi)->exists());
        
        $penjualan = Penjualan::create([
            'nomor_transaksi' => $nomorTransaksi,
            'tanggal_transaksi' => $randomTime,
            'user_id' => $kasir->id,
            'nama_pelanggan' => 'Pelanggan ' . rand(1, 100),
            'telepon_pelanggan' => '081' . rand(10000000, 99999999),
            'alamat_pelanggan' => 'Alamat ' . rand(1, 50),
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => collect(['tunai', 'transfer'])->random(),
            'status' => 'selesai',
            'catatan' => 'Test data untuk laporan bulanan Juni 2025',
            'subtotal' => 0,
            'total' => 0,
            'created_at' => $randomTime,
            'updated_at' => $randomTime,
        ]);
        
        // Add 1-3 items per transaction
        $itemCount = rand(1, 3);
        $subtotal = 0;
        
        for ($i = 0; $i < $itemCount; $i++) {
            $barang = $barangs->random();
            $jumlah = rand(1, 3);
            $hargaSatuan = $barang->harga_jual;
            $itemSubtotal = $jumlah * $hargaSatuan;
            
            DetailPenjualan::create([
                'penjualan_id' => $penjualan->id,
                'barang_id' => $barang->id,
                'jumlah' => $jumlah,
                'harga_satuan' => $hargaSatuan,
                'subtotal' => $itemSubtotal,
            ]);
            
            // Record stock movement
            $barang->recordStockMovement(
                'out',
                $jumlah,
                "Penjualan {$nomorTransaksi}",
                $kasir->id,
                'App\Models\Penjualan',
                $penjualan->id,
                $hargaSatuan
            );
            
            $subtotal += $itemSubtotal;
        }
        
        // Update totals
        $penjualan->update([
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);
    }
    
    private function createStockMovement(Carbon $date, User $karyawan, $barangs): void
    {
        // Random time during working hours
        $randomTime = $date->copy()
            ->setHour(rand(8, 17))
            ->setMinute(rand(0, 59))
            ->setSecond(rand(0, 59));
        
        $barang = $barangs->random();
        $type = collect(['in', 'adjustment'])->random();
        
        $quantity = match($type) {
            'in' => rand(10, 50), // Restock
            'adjustment' => rand(-5, 5), // Small adjustments
            default => rand(1, 10),
        };
        
        $description = match($type) {
            'in' => 'Restock dari supplier - ' . $randomTime->format('d/m/Y'),
            'adjustment' => 'Penyesuaian stok - ' . $randomTime->format('d/m/Y'),
            default => 'Pergerakan stok - ' . $randomTime->format('d/m/Y'),
        };
        
        // Create stock movement with specific timestamp
        StockMovement::create([
            'barang_id' => $barang->id,
            'user_id' => $karyawan->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $barang->stok,
            'stock_after' => $barang->stok + $quantity,
            'unit_price' => $type === 'in' ? $barang->harga_beli : null,
            'description' => $description,
            'created_at' => $randomTime,
            'updated_at' => $randomTime,
        ]);
        
        // Update barang stock
        $barang->update(['stok' => $barang->stok + $quantity]);
    }
}
