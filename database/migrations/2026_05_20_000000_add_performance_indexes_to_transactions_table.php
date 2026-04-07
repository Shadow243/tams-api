<?php

declare(strict_types=1);

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
        Schema::table('transactions', function (Blueprint $table) {
            // Standalone created_at index for pure date-range queries
            $table->index('created_at', 'transactions_created_at_index');

            // Composite index: currency_id + created_at (used in dashboard stats
            // when filtering by currency and grouping by date)
            $table->index(
                ['currency_id', 'created_at'],
                'transactions_currency_id_created_at_index'
            );

            // Composite index: transaction_type_id + created_at (used in
            // dashboard stats when filtering by type and grouping by date)
            $table->index(
                ['transaction_type_id', 'created_at'],
                'transactions_transaction_type_id_created_at_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_created_at_index');
            $table->dropIndex('transactions_currency_id_created_at_index');
            $table->dropIndex('transactions_transaction_type_id_created_at_index');
        });
    }
};
