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
        // Fix unique constraints after table rename
        Schema::table('pengguna', function (Blueprint $table) {
            // Drop old constraints if they exist
            try {
                $table->dropUnique('users_email_unique');
            } catch (Exception $e) {
                // Constraint might not exist
            }
            try {
                $table->dropUnique('users_username_unique');
            } catch (Exception $e) {
                // Constraint might not exist
            }
            
            // Add new constraints with correct names
            $table->unique('email', 'pengguna_email_unique');
            $table->unique('username', 'pengguna_username_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the constraint fixes
        Schema::table('pengguna', function (Blueprint $table) {
            // Drop new constraints
            try {
                $table->dropUnique('pengguna_email_unique');
            } catch (Exception $e) {
                // Constraint might not exist
            }
            try {
                $table->dropUnique('pengguna_username_unique');
            } catch (Exception $e) {
                // Constraint might not exist
            }
            
            // Add back old constraints
            $table->unique('email', 'users_email_unique');
            $table->unique('username', 'users_username_unique');
        });
    }
};
