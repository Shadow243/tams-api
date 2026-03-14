<?php

declare(strict_types=1);

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
        // Drop foreign key constraint from transactions if it exists
        if (Schema::hasTable('transactions')) {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transactions' 
                AND COLUMN_NAME = 'customer_id'
                AND CONSTRAINT_NAME != 'PRIMARY'
            ");
            
            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE transactions DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        }

        // Drop and recreate customers table with correct structure
        Schema::dropIfExists('customers');
        
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('full_name');
            $table->string('phone', 20)->unique()->index();
            $table->string('national_id', 50)->nullable()->unique()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // Update transactions customer_id to integer if needed
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'customer_id')) {
            // Check if customer_id is UUID type
            $columnType = DB::select("
                SELECT DATA_TYPE, COLUMN_TYPE
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transactions' 
                AND COLUMN_NAME = 'customer_id'
            ");
            
            if (!empty($columnType) && $columnType[0]->DATA_TYPE === 'char') {
                // Change from UUID to integer
                DB::statement('ALTER TABLE transactions MODIFY COLUMN customer_id BIGINT UNSIGNED NULL');
            }
            
            // Add back foreign key constraint
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key from transactions
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        }

        // Drop and recreate customers with same structure
        Schema::dropIfExists('customers');
        
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('full_name');
            $table->string('phone', 20)->unique()->index();
            $table->string('national_id', 50)->nullable()->unique()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // Change transactions customer_id back to UUID if needed
        if (Schema::hasTable('transactions')) {
            DB::statement('ALTER TABLE transactions MODIFY COLUMN customer_id CHAR(36) NULL');
            
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('restrict');
            });
        }
    }
};
