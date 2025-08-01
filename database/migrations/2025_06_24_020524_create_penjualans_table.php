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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // kasir/karyawan yang melayani
            $table->foreignId('pelanggan_id')->nullable()->constrained('users')->onDelete('set null'); // pelanggan (bisa null untuk walk-in customer)
            $table->string('nama_pelanggan')->nullable(); // untuk walk-in customer
            $table->string('telepon_pelanggan')->nullable();
            $table->text('alamat_pelanggan')->nullable();
            $table->enum('jenis_transaksi', ['offline', 'online'])->default('offline');
            $table->enum('status', ['pending', 'selesai', 'dibatalkan'])->default('pending');
            $table->enum('metode_pembayaran', ['tunai', 'transfer', 'kartu_debit', 'kartu_kredit'])->default('tunai');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('pajak', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('bayar', 15, 2)->nullable();
            $table->decimal('kembalian', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->default(0); // Total harga pokok penjualan
            $table->decimal('total_profit', 15, 2)->default(0); // Total keuntungan
            $table->boolean('is_financial_recorded')->default(false); // Apakah sudah dicatat ke keuangan
            $table->text('catatan')->nullable();
            $table->timestamp('tanggal_transaksi');
            $table->unsignedBigInteger('financial_account_id')->nullable()->after('id');
            $table->foreign('financial_account_id')->references('id')->on('akun_keuangan')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
