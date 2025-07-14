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
        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');                                    // Tanggal pengeluaran
            $table->string('keterangan');                               // Keterangan pengeluaran
            $table->decimal('jumlah', 15, 2);                          // Jumlah pengeluaran
            $table->enum('kategori', [
                'operasional', 'gaji', 'pembelian', 'maintenance', 'marketing', 'lainnya'
            ])->default('lainnya');                                     // Kategori pengeluaran
            $table->enum('metode_pembayaran', [
                'tunai', 'transfer', 'kartu_debit', 'kartu_kredit'
            ])->default('tunai');                                       // Metode pembayaran
            $table->enum('status', [
                'draft', 'pending', 'approved', 'paid', 'cancelled'
            ])->default('draft');                                        // Status pengeluaran
            $table->string('bukti_pembayaran')->nullable();             // Path bukti pembayaran
            $table->text('catatan')->nullable();                        // Catatan tambahan
            $table->foreignId('created_by')                             // User yang membuat
                  ->constrained('pengguna')
                  ->onDelete('cascade');
            $table->foreignId('approved_by')                            // User yang menyetujui
                  ->nullable()
                  ->constrained('pengguna')
                  ->onDelete('set null');
            $table->timestamp('approved_at')->nullable();               // Waktu persetujuan
            $table->unsignedBigInteger('financial_account_id')->nullable();
            $table->foreign('financial_account_id')->references('id')->on('akun_keuangan')->nullOnDelete();
            $table->timestamps();

            // Indexes untuk performa
            $table->index(['tanggal', 'kategori']);
            $table->index(['status', 'kategori']);
            $table->index(['created_by', 'tanggal']);
            $table->index('kategori');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
    }
};
