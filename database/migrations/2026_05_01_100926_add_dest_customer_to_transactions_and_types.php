<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->boolean('dest_customer_required')->default(false)->after('dest_wallet_amount');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('dest_customer_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['dest_customer_id']);
            $table->dropColumn('dest_customer_id');
        });

        Schema::table('transaction_types', function (Blueprint $table) {
            $table->dropColumn('dest_customer_required');
        });
    }
};
