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
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->date('flow_date');
            $table->enum('flow_type', ['operating', 'investing', 'financing']);
            $table->enum('direction', ['inflow', 'outflow']);
            $table->string('category'); // e.g., 'sales', 'purchases', 'salaries', 'equipment'
            $table->decimal('amount', 15, 2);
            $table->foreignId('account_id')->constrained('akun_keuangan');
            $table->foreignId('transaction_id')->nullable()->constrained('financial_transactions');
            $table->text('description');
            $table->decimal('running_balance', 15, 2); // Saldo berjalan
            $table->timestamps();

            $table->index(['flow_date', 'flow_type']);
            $table->index(['account_id', 'flow_date']);
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};
