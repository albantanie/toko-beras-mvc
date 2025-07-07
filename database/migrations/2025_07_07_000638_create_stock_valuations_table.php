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
        Schema::create('stock_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barangs');
            $table->date('valuation_date');
            $table->integer('quantity_on_hand'); // Stok fisik
            $table->decimal('unit_cost', 12, 2); // Harga pokok per unit
            $table->decimal('unit_price', 12, 2); // Harga jual per unit
            $table->decimal('total_cost_value', 15, 2)->default(0); // Total nilai cost
            $table->decimal('total_market_value', 15, 2)->default(0); // Total nilai pasar
            $table->decimal('potential_profit', 15, 2)->default(0); // Potensi keuntungan
            $table->decimal('profit_margin_percentage', 8, 2)->default(0); // Margin keuntungan %
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->default('fifo');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['barang_id', 'valuation_date']);
            $table->index('valuation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_valuations');
    }
};
