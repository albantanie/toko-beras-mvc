<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create daily reports table for kasir (transactions) and karyawan (stock)
     */
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');                                    // Tanggal laporan
            $table->enum('type', ['transaction', 'stock']);                // Jenis laporan: transaksi atau stock
            $table->foreignId('user_id')->constrained('users');            // User yang membuat laporan (kasir/karyawan)
            $table->json('data');                                          // Data laporan dalam JSON
            $table->decimal('total_amount', 15, 2)->nullable();            // Total transaksi (untuk kasir)
            $table->integer('total_transactions')->nullable();              // Jumlah transaksi (untuk kasir)
            $table->integer('total_items_sold')->nullable();               // Total item terjual (untuk kasir)
            $table->integer('total_stock_movements')->nullable();          // Total pergerakan stock (untuk karyawan)
            $table->decimal('total_stock_value', 15, 2)->nullable();       // Total nilai stock (untuk karyawan)
            $table->text('notes')->nullable();                            // Catatan tambahan
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                  ->default('submitted');                                   // Status laporan
            $table->foreignId('approved_by')->nullable()
                  ->constrained('users')->onDelete('set null');            // Owner yang approve
            $table->timestamp('approved_at')->nullable();                  // Waktu approval
            $table->text('approval_notes')->nullable();                    // Catatan approval
            $table->timestamps();

            // Indexes untuk performa
            $table->index(['report_date', 'type']);
            $table->index(['user_id', 'report_date']);
            $table->index(['type', 'status']);
            $table->unique(['report_date', 'type', 'user_id'], 'unique_daily_report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
