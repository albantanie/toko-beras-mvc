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
        Schema::table('pergerakan_stok', function (Blueprint $table) {
            // Tambah field untuk nilai stock yang lebih jelas
            $table->decimal('stock_value_in', 15, 2)->default(0)->after('total_value'); // Nilai stock masuk
            $table->decimal('stock_value_out', 15, 2)->default(0)->after('stock_value_in'); // Nilai stock keluar
            $table->decimal('stock_value_change', 15, 2)->default(0)->after('stock_value_out'); // Perubahan nilai stock (+ atau -)

            // Tambah field untuk tracking harga yang lebih detail
            $table->decimal('purchase_price', 10, 2)->nullable()->after('unit_cost'); // Harga beli saat stock masuk
            $table->decimal('selling_price', 10, 2)->nullable()->after('purchase_price'); // Harga jual saat stock keluar

            // Tambah field untuk keterangan yang lebih detail
            $table->text('value_calculation_notes')->nullable()->after('description'); // Keterangan perhitungan nilai
            $table->string('movement_category', 50)->nullable()->after('value_calculation_notes'); // Kategori pergerakan (purchase, sale, adjustment, etc.)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pergerakan_stok', function (Blueprint $table) {
            $table->dropColumn([
                'stock_value_in',
                'stock_value_out',
                'stock_value_change',
                'purchase_price',
                'selling_price',
                'value_calculation_notes',
                'movement_category'
            ]);
        });
    }
};
