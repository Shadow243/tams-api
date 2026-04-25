<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->enum('dest_wallet_effect', ['none', 'debit', 'credit'])->default('none')->after('dest_branch_amount');
            $table->enum('dest_wallet_amount', ['gross', 'net', 'fee'])->default('gross')->after('dest_wallet_effect');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('dest_wallet_id')->nullable()->constrained('wallets')->nullOnDelete()->after('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['dest_wallet_id']);
            $table->dropColumn('dest_wallet_id');
        });

        Schema::table('transaction_types', function (Blueprint $table) {
            $table->dropColumn(['dest_wallet_effect', 'dest_wallet_amount']);
        });
    }
};
