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
        // First, let's create a temporary column for currency_id
        Schema::table('wallets', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('balance')->constrained()->onDelete('restrict');
        });

        // Map existing currency codes to currency IDs
        // Assuming currencies table has been seeded with common currencies
        $currencies = DB::table('currencies')->get()->keyBy('code');
        
        $wallets = DB::table('wallets')->get();
        foreach ($wallets as $wallet) {
            $currencyCode = $wallet->currency ?? 'USD';
            $currency = $currencies->get($currencyCode);
            
            if ($currency) {
                DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->update(['currency_id' => $currency->id]);
            } else {
                // If currency code not found, use default currency
                $defaultCurrency = $currencies->firstWhere('is_default', true);
                if ($defaultCurrency) {
                    DB::table('wallets')
                        ->where('id', $wallet->id)
                        ->update(['currency_id' => $defaultCurrency->id]);
                }
            }
        }

        // Remove the unique constraint that includes currency
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique(['wallet_number', 'operator_id', 'currency']);
        });

        // Drop the old currency column
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        // Make currency_id NOT NULL after data migration
        Schema::table('wallets', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable(false)->change();
        });

        // Add new unique constraint with currency_id
        Schema::table('wallets', function (Blueprint $table) {
            $table->unique(['wallet_number', 'operator_id', 'currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the unique constraint with currency_id
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique(['wallet_number', 'operator_id', 'currency_id']);
        });

        // Add back the currency string column
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('currency', 10)->default('USD')->after('balance');
        });

        // Populate currency column from currency_id
        $wallets = DB::table('wallets')
            ->join('currencies', 'wallets.currency_id', '=', 'currencies.id')
            ->select('wallets.id', 'currencies.code')
            ->get();

        foreach ($wallets as $wallet) {
            DB::table('wallets')
                ->where('id', $wallet->id)
                ->update(['currency' => $wallet->code]);
        }

        // Drop the foreign key and currency_id column
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });

        // Restore the old unique constraint
        Schema::table('wallets', function (Blueprint $table) {
            $table->unique(['wallet_number', 'operator_id', 'currency']);
        });
    }
};
