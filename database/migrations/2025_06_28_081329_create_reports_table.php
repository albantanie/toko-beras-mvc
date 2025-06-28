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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');                                // Judul laporan
            $table->enum('type', [
                'penjualan_summary', 'penjualan_detail', 'transaksi_harian', 
                'transaksi_kasir', 'barang_stok', 'barang_movement', 
                'barang_performance', 'keuangan'
            ]); // Jenis laporan yang lebih spesifik
            $table->json('data');                                   // Data laporan dalam JSON
            $table->text('summary')->nullable();                   // Ringkasan laporan
            $table->date('period_from');                           // Periode awal laporan
            $table->date('period_to');                             // Periode akhir laporan
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])
                  ->default('draft');                              // Status approval
            $table->foreignId('generated_by')                     // User yang generate laporan
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('approved_by')                      // Owner yang approve
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('approved_at')->nullable();          // Waktu approval
            $table->text('approval_notes')->nullable();            // Catatan approval/rejection
            $table->string('file_path')->nullable();               // Path file laporan (PDF/Excel)
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'type']);
            $table->index(['period_from', 'period_to']);
            $table->index(['generated_by', 'approved_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
