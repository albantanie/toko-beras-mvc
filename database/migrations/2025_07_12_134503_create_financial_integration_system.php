<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if financial_accounts table exists before trying to modify it
        if (Schema::hasTable('financial_accounts')) {
            // Tambahkan kolom untuk tracking financial integration
            Schema::table('financial_accounts', function (Blueprint $table) {
                $table->boolean('auto_update_balance')->default(true)->after('is_active');
            });

            // Buat view untuk total cash summary
            DB::statement("
                CREATE OR REPLACE VIEW cash_summary AS
                SELECT 
                    SUM(CASE WHEN account_type IN ('cash', 'bank') THEN current_balance ELSE 0 END) as total_cash,
                    SUM(CASE WHEN account_type = 'cash' THEN current_balance ELSE 0 END) as cash_balance,
                    SUM(CASE WHEN account_type = 'bank' THEN current_balance ELSE 0 END) as bank_balance,
                    SUM(CASE WHEN account_type = 'receivable' THEN current_balance ELSE 0 END) as total_receivables,
                    SUM(CASE WHEN account_type = 'payable' THEN current_balance ELSE 0 END) as total_payables
                FROM financial_accounts 
                WHERE is_active = true
            ");

            // Buat stored procedure untuk update balance
            DB::statement("
                CREATE OR REPLACE FUNCTION update_account_balance(
                    p_account_id BIGINT,
                    p_amount DECIMAL(15,2),
                    p_operation VARCHAR(10) DEFAULT 'add'
                ) RETURNS DECIMAL(15,2) AS $$
                DECLARE
                    new_balance DECIMAL(15,2);
                BEGIN
                    IF p_operation = 'add' THEN
                        UPDATE financial_accounts 
                        SET current_balance = current_balance + p_amount,
                            updated_at = NOW()
                        WHERE id = p_account_id AND auto_update_balance = true;
                    ELSE
                        UPDATE financial_accounts 
                        SET current_balance = current_balance - p_amount,
                            updated_at = NOW()
                        WHERE id = p_account_id AND auto_update_balance = true;
                    END IF;
                    
                    SELECT current_balance INTO new_balance 
                    FROM financial_accounts 
                    WHERE id = p_account_id;
                    
                    RETURN new_balance;
                END;
                $$ LANGUAGE plpgsql;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP FUNCTION IF EXISTS update_account_balance(BIGINT, DECIMAL, VARCHAR)");
        DB::statement("DROP VIEW IF EXISTS cash_summary");
        
        if (Schema::hasTable('financial_accounts')) {
            Schema::table('financial_accounts', function (Blueprint $table) {
                $table->dropColumn(['auto_update_balance']);
            });
        }
    }
};
