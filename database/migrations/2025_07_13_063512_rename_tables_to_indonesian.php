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
        if (Schema::hasTable('barangs')) {
            Schema::rename('barangs', 'produk');
        }
        if (Schema::hasTable('stock_valuations')) {
            Schema::rename('stock_valuations', 'stok_barang');
        }
        if (Schema::hasTable('daily_reports')) {
            Schema::rename('daily_reports', 'laporan');
        }
        if (Schema::hasTable('detail_penjualans')) {
            Schema::rename('detail_penjualans', 'rekap');
        }
        if (Schema::hasTable('financial_transactions')) {
            Schema::rename('financial_transactions', 'pembayaran');
        }

        // Additional tables to rename
        if (Schema::hasTable('users')) {
            Schema::rename('users', 'pengguna');
        }
        if (Schema::hasTable('roles')) {
            Schema::rename('roles', 'peran');
        }
        if (Schema::hasTable('user_roles')) {
            Schema::rename('user_roles', 'pengguna_peran');
        }
        if (Schema::hasTable('penjualans')) {
            Schema::rename('penjualans', 'transaksi');
        }
        if (Schema::hasTable('stock_movements')) {
            Schema::rename('stock_movements', 'pergerakan_stok');
        }
        if (Schema::hasTable('pdf_reports')) {
            Schema::rename('pdf_reports', 'laporan_pdf');
        }
        if (Schema::hasTable('financial_accounts')) {
            Schema::rename('financial_accounts', 'akun_keuangan');
        }
        if (Schema::hasTable('payrolls')) {
            Schema::rename('payrolls', 'gaji');
        }
        if (Schema::hasTable('cash_flows')) {
            Schema::rename('cash_flows', 'arus_kas');
        }
        if (Schema::hasTable('budgets')) {
            Schema::rename('budgets', 'anggaran');
        }
        if (Schema::hasTable('payroll_configurations')) {
            Schema::rename('payroll_configurations', 'konfigurasi_gaji');
        }
        if (Schema::hasTable('purchases')) {
            Schema::rename('purchases', 'pembelian');
        }
        if (Schema::hasTable('purchase_details')) {
            Schema::rename('purchase_details', 'detail_pembelian');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the table renames (only existing tables)
        if (Schema::hasTable('produk')) {
            Schema::rename('produk', 'barangs');
        }
        if (Schema::hasTable('stok_barang')) {
            Schema::rename('stok_barang', 'stock_valuations');
        }
        if (Schema::hasTable('laporan')) {
            Schema::rename('laporan', 'daily_reports');
        }
        if (Schema::hasTable('rekap')) {
            Schema::rename('rekap', 'detail_penjualans');
        }
        if (Schema::hasTable('pembayaran')) {
            Schema::rename('pembayaran', 'financial_transactions');
        }

        // Additional tables to reverse
        if (Schema::hasTable('pengguna')) {
            Schema::rename('pengguna', 'users');
        }
        if (Schema::hasTable('peran')) {
            Schema::rename('peran', 'roles');
        }
        if (Schema::hasTable('pengguna_peran')) {
            Schema::rename('pengguna_peran', 'user_roles');
        }
        if (Schema::hasTable('transaksi')) {
            Schema::rename('transaksi', 'penjualans');
        }
        if (Schema::hasTable('pergerakan_stok')) {
            Schema::rename('pergerakan_stok', 'stock_movements');
        }
        if (Schema::hasTable('laporan_pdf')) {
            Schema::rename('laporan_pdf', 'pdf_reports');
        }
        if (Schema::hasTable('akun_keuangan')) {
            Schema::rename('akun_keuangan', 'financial_accounts');
        }
        if (Schema::hasTable('gaji')) {
            Schema::rename('gaji', 'payrolls');
        }
        if (Schema::hasTable('arus_kas')) {
            Schema::rename('arus_kas', 'cash_flows');
        }
        if (Schema::hasTable('anggaran')) {
            Schema::rename('anggaran', 'budgets');
        }
        if (Schema::hasTable('konfigurasi_gaji')) {
            Schema::rename('konfigurasi_gaji', 'payroll_configurations');
        }
        if (Schema::hasTable('pembelian')) {
            Schema::rename('pembelian', 'purchases');
        }
        if (Schema::hasTable('detail_pembelian')) {
            Schema::rename('detail_pembelian', 'purchase_details');
        }
    }
};
