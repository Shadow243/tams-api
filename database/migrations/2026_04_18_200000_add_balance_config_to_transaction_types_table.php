<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Balance configuration fields:
     * - branch_effect:               none | debit | credit  (branch loses/gains cash)
     * - branch_amount:               gross | net | fee       (which amount applies to branch)
     * - wallet_effect:               none | debit | credit  (wallet loses/gains virtual balance)
     * - wallet_amount:               gross | net | fee       (which amount applies to wallet)
     * - dest_branch_effect:          none | debit | credit  (destination branch loses/gains cash)
     * - dest_branch_amount:          gross | net | fee       (which amount applies to dest branch)
     */
    public function up(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->enum('branch_effect', ['none', 'debit', 'credit'])->default('none')->after('description');
            $table->enum('branch_amount', ['gross', 'net', 'fee'])->default('gross')->after('branch_effect');
            $table->enum('wallet_effect', ['none', 'debit', 'credit'])->default('none')->after('branch_amount');
            $table->enum('wallet_amount', ['gross', 'net', 'fee'])->default('gross')->after('wallet_effect');
            $table->enum('dest_branch_effect', ['none', 'debit', 'credit'])->default('none')->after('wallet_amount');
            $table->enum('dest_branch_amount', ['gross', 'net', 'fee'])->default('gross')->after('dest_branch_effect');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->dropColumn([
                'branch_effect', 'branch_amount',
                'wallet_effect', 'wallet_amount',
                'dest_branch_effect', 'dest_branch_amount',
            ]);
        });
    }
};
