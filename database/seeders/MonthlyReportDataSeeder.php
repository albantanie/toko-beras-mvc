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
 * Seeder untuk generate data laporan bulanan
 * 
 * Seeder ini akan membuat:
 * - Data transaksi penjualan untuk 3 bulan terakhir
 * - Data stock movements untuk 3 bulan terakhir  
 * - Daily reports untuk kasir dan karyawan
 * - Data yang cukup untuk generate laporan bulanan
 */
class MonthlyReportDataSeeder extends Seeder
{
    private $kasirUsers = [];
    private $karyawanUsers = [];
    private $barangs = [];
    
    public function run(): void
    {
        $this->command->info('🚀 Starting Monthly Report Data Seeder...');
        
        // Get users
        $this->getUsers();
        
        // Get barangs
        $this->getBarangs();
        
        // Generate data for last 3 months
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths(3)->startOfMonth();
        
        $this->command->info("📅 Generating data from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        
        // Generate data month by month
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();
            
            $this->command->info("📊 Generating data for {$monthStart->format('F Y')}");
            
            // Generate transactions for this month
            $this->generateMonthlyTransactions($monthStart, $monthEnd);
            
            // Generate stock movements for this month
            $this->generateMonthlyStockMovements($monthStart, $monthEnd);
            
            // Generate daily reports for this month
            $this->generateDailyReports($monthStart, $monthEnd);
            
            $currentDate->addMonth();
        }
        
        $this->command->info('✅ Monthly Report Data Seeder completed successfully!');
    }
    
    private function getUsers(): void
    {
        $this->kasirUsers = User::whereHas('roles', function($q) {
            $q->where('name', 'kasir');
        })->get();
        
        $this->karyawanUsers = User::whereHas('roles', function($q) {
            $q->where('name', 'karyawan');
        })->get();
        
        if ($this->kasirUsers->isEmpty()) {
            $this->command->warn('⚠️  No kasir users found. Creating sample kasir user...');
            $kasir = User::create([
                'name' => 'Kasir Sample',
                'email' => 'kasir.sample@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $kasir->assignRole('kasir');
            $this->kasirUsers = collect([$kasir]);
        }
        
        if ($this->karyawanUsers->isEmpty()) {
            $this->command->warn('⚠️  No karyawan users found. Creating sample karyawan user...');
            $karyawan = User::create([
                'name' => 'Karyawan Sample',
                'email' => 'karyawan.sample@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $karyawan->assignRole('karyawan');
            $this->karyawanUsers = collect([$karyawan]);
        }
        
        $this->command->info("👥 Found {$this->kasirUsers->count()} kasir users and {$this->karyawanUsers->count()} karyawan users");
    }
    
    private function getBarangs(): void
    {
        $this->barangs = Barang::where('is_active', true)->get();
        
        if ($this->barangs->isEmpty()) {
            $this->command->warn('⚠️  No active barangs found. Creating sample barangs...');
            
            $sampleBarangs = [
                [
                    'nama' => 'Beras Premium 5kg',
                    'kategori' => 'Beras Premium',
                    'harga_beli' => 45000,
                    'harga_jual' => 55000,
                    'stok' => 100,
                    'stok_minimum' => 10,
                    'satuan' => 'kg',
                    'kode_barang' => 'BRP-5KG',
                ],
                [
                    'nama' => 'Beras Medium 10kg',
                    'kategori' => 'Beras Medium',
                    'harga_beli' => 85000,
                    'harga_jual' => 95000,
                    'stok' => 80,
                    'stok_minimum' => 15,
                    'satuan' => 'kg',
                    'kode_barang' => 'BRM-10KG',
                ],
                [
                    'nama' => 'Beras Ekonomis 25kg',
                    'kategori' => 'Beras Ekonomis',
                    'harga_beli' => 180000,
                    'harga_jual' => 200000,
                    'stok' => 50,
                    'stok_minimum' => 5,
                    'satuan' => 'kg',
                    'kode_barang' => 'BRE-25KG',
                ],
            ];
            
            foreach ($sampleBarangs as $barangData) {
                $barangData['is_active'] = true;
                $barangData['created_by'] = 1;
                $barangData['updated_by'] = 1;
                $this->barangs->push(Barang::create($barangData));
            }
        }
        
        $this->command->info("📦 Found {$this->barangs->count()} active barangs");
    }
    
    private function generateMonthlyTransactions(Carbon $monthStart, Carbon $monthEnd): void
    {
        $kasir = $this->kasirUsers->random();
        $daysInMonth = $monthStart->daysInMonth;
        
        // Generate 3-8 transactions per day (random)
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $monthStart->copy()->addDays($day - 1);
            
            // Skip if date is in the future
            if ($currentDate > Carbon::now()) {
                continue;
            }
            
            $transactionsPerDay = rand(3, 8);
            
            for ($i = 0; $i < $transactionsPerDay; $i++) {
                $this->createTransaction($currentDate, $kasir);
            }
        }
        
        $this->command->info("💰 Generated transactions for {$monthStart->format('F Y')}");
    }
    
    private function createTransaction(Carbon $date, User $kasir): void
    {
        // Random time during business hours (8 AM - 8 PM)
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
            'catatan' => 'Transaksi seeder untuk laporan bulanan',
            'subtotal' => 0, // Will be calculated
            'total' => 0, // Will be calculated
        ]);
        
        // Add 1-4 items per transaction
        $itemCount = rand(1, 4);
        $subtotal = 0;

        for ($i = 0; $i < $itemCount; $i++) {
            $barang = $this->barangs->random();
            $jumlah = rand(1, 5);
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

        // Update subtotal and total
        $penjualan->update([
            'subtotal' => $subtotal,
            'total' => $subtotal, // No discount or tax for simplicity
        ]);
    }
    
    private function generateMonthlyStockMovements(Carbon $monthStart, Carbon $monthEnd): void
    {
        $karyawan = $this->karyawanUsers->random();
        
        // Generate stock in movements (restocking) - 2-4 times per month
        $restockDays = rand(2, 4);
        for ($i = 0; $i < $restockDays; $i++) {
            $restockDate = $monthStart->copy()->addDays(rand(0, $monthStart->daysInMonth - 1));
            
            // Skip if date is in the future
            if ($restockDate > Carbon::now()) {
                continue;
            }
            
            $this->createStockMovements($restockDate, $karyawan, 'in');
        }
        
        // Generate stock adjustments - 1-2 times per month
        $adjustmentDays = rand(1, 2);
        for ($i = 0; $i < $adjustmentDays; $i++) {
            $adjustmentDate = $monthStart->copy()->addDays(rand(0, $monthStart->daysInMonth - 1));
            
            // Skip if date is in the future
            if ($adjustmentDate > Carbon::now()) {
                continue;
            }
            
            $this->createStockMovements($adjustmentDate, $karyawan, 'adjustment');
        }
        
        $this->command->info("📦 Generated stock movements for {$monthStart->format('F Y')}");
    }
    
    private function createStockMovements(Carbon $date, User $karyawan, string $type): void
    {
        // Random time during working hours
        $randomTime = $date->copy()
            ->setHour(rand(8, 17))
            ->setMinute(rand(0, 59))
            ->setSecond(rand(0, 59));
        
        // Process 1-3 barangs per movement session
        $barangCount = rand(1, 3);
        $selectedBarangs = $this->barangs->random($barangCount);
        
        foreach ($selectedBarangs as $barang) {
            $quantity = match($type) {
                'in' => rand(20, 100), // Restock
                'adjustment' => rand(-10, 10), // Small adjustments
                default => rand(1, 20),
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
    
    private function generateDailyReports(Carbon $monthStart, Carbon $monthEnd): void
    {
        $daysInMonth = $monthStart->daysInMonth;
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $monthStart->copy()->addDays($day - 1);
            
            // Skip if date is in the future
            if ($currentDate > Carbon::now()) {
                continue;
            }
            
            // Generate daily transaction reports for each kasir
            foreach ($this->kasirUsers as $kasir) {
                DailyReport::generateTransactionReport($currentDate, $kasir->id);
            }
            
            // Generate daily stock reports for each karyawan
            foreach ($this->karyawanUsers as $karyawan) {
                DailyReport::generateStockReport($currentDate, $karyawan->id);
            }
        }
        
        $this->command->info("📋 Generated daily reports for {$monthStart->format('F Y')}");
    }
}
