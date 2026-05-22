<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->boolean('requires_dest_customer')->default(false)->after('customer_account_amount');
        });

        // Activer pour les types qui nécessitent un bénéficiaire
        DB::table('transaction_types')
            ->whereIn('code', ['cash_deposit_transfer'])
            ->update(['requires_dest_customer' => true]);
    }

    public function down(): void
    {
        Schema::table('transaction_types', function (Blueprint $table) {
            $table->dropColumn('requires_dest_customer');
        });
    }
};
