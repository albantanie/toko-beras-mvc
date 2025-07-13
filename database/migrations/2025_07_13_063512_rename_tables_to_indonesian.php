<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename tables to Indonesian names (only existing tables)
        Schema::rename('barangs', 'produk');
        Schema::rename('stock_valuations', 'stok_barang');
        Schema::rename('daily_reports', 'laporan');
        Schema::rename('detail_penjualans', 'rekap');
        Schema::rename('financial_transactions', 'pembayaran');

        // Additional tables to rename
        Schema::rename('users', 'pengguna');
        Schema::rename('roles', 'peran');
        Schema::rename('user_roles', 'pengguna_peran');
        Schema::rename('penjualans', 'transaksi');
        Schema::rename('stock_movements', 'pergerakan_stok');
        Schema::rename('pdf_reports', 'laporan_pdf');
        Schema::rename('financial_accounts', 'akun_keuangan');
        Schema::rename('payrolls', 'gaji');
        Schema::rename('cash_flows', 'arus_kas');
        Schema::rename('budgets', 'anggaran');
        Schema::rename('payroll_configurations', 'konfigurasi_gaji');
        Schema::rename('purchases', 'pembelian');
        Schema::rename('purchase_details', 'detail_pembelian');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the table renames (only existing tables)
        Schema::rename('produk', 'barangs');
        Schema::rename('stok_barang', 'stock_valuations');
        Schema::rename('laporan', 'daily_reports');
        Schema::rename('rekap', 'detail_penjualans');
        Schema::rename('pembayaran', 'financial_transactions');

        // Additional tables to reverse
        Schema::rename('pengguna', 'users');
        Schema::rename('peran', 'roles');
        Schema::rename('pengguna_peran', 'user_roles');
        Schema::rename('transaksi', 'penjualans');
        Schema::rename('pergerakan_stok', 'stock_movements');
        Schema::rename('laporan_pdf', 'pdf_reports');
        Schema::rename('akun_keuangan', 'financial_accounts');
        Schema::rename('gaji', 'payrolls');
        Schema::rename('arus_kas', 'cash_flows');
        Schema::rename('anggaran', 'budgets');
        Schema::rename('konfigurasi_gaji', 'payroll_configurations');
        Schema::rename('pembelian', 'purchases');
        Schema::rename('detail_pembelian', 'purchase_details');
    }
};
