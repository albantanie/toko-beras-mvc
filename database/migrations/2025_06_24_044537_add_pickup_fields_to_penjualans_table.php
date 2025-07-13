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
        Schema::table('transaksi', function (Blueprint $table) {
            $table->enum('pickup_method', ['self', 'grab', 'gojek', 'other'])->default('self')->after('jenis_transaksi');
            $table->string('pickup_person_name')->nullable()->after('pickup_method');
            $table->string('pickup_person_phone')->nullable()->after('pickup_person_name');
            $table->text('pickup_notes')->nullable()->after('pickup_person_phone');
            $table->timestamp('pickup_time')->nullable()->after('pickup_notes');
            $table->string('receipt_code')->nullable()->after('pickup_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_method',
                'pickup_person_name',
                'pickup_person_phone',
                'pickup_notes',
                'pickup_time',
                'receipt_code'
            ]);
        });
    }
};
