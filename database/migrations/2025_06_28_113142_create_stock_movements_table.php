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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barangs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Siapa yang melakukan perubahan
            $table->enum('type', [
                'in',           // Stock masuk (pembelian, restock)
                'out',          // Stock keluar (penjualan, kerusakan)
                'adjustment',   // Penyesuaian manual
                'correction',   // Koreksi kesalahan
                'initial',      // Stock awal
                'return',       // Retur dari pelanggan
                'damage',       // Kerusakan/expired
                'transfer'      // Transfer antar gudang
            ]);
            $table->integer('quantity'); // Jumlah perubahan (bisa positif/negatif)
            $table->integer('stock_before'); // Stock sebelum perubahan
            $table->integer('stock_after'); // Stock setelah perubahan
            $table->decimal('unit_price', 10, 2)->nullable(); // Harga per unit saat perubahan
            $table->text('description'); // Keterangan perubahan
            $table->string('reference_type')->nullable(); // Jenis referensi (penjualan, pembelian, manual, dll)
            $table->unsignedBigInteger('reference_id')->nullable(); // ID referensi (ID penjualan, pembelian, dll)
            $table->json('metadata')->nullable(); // Data tambahan dalam format JSON
            $table->timestamps();

            // Indexes untuk performa query
            $table->index(['barang_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
