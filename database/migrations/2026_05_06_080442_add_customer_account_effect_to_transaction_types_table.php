<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->enum('customer_account_effect', ['none', 'debit', 'credit'])->default('none')->after('dest_wallet_amount');
            $table->enum('customer_account_amount', ['gross', 'net', 'fee'])->default('gross')->after('customer_account_effect');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->dropColumn(['customer_account_effect', 'customer_account_amount']);
        });
    }
};
