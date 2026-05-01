<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->dropColumn('dest_customer_required');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->boolean('dest_customer_required')->default(false)->after('dest_wallet_amount');
        });
    }
};
