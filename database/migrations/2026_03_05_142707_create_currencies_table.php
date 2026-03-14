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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // CDF, USD, UGX, etc.
            $table->string('name'); // Franc Congolais, US Dollar, etc.
            $table->string('symbol', 10); // FC, $, USh, etc.
            $table->tinyInteger('decimal_places')->default(2); // 0 for CDF, 2 for USD
            $table->decimal('exchange_rate', 15, 6)->default(1.000000); // Rate to base currency
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
