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
        Schema::create('branch_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('currency_code', 3);
            $table->decimal('cash_balance', 15, 2)->default(0);
            $table->timestamps();

            // Une branche ne peut avoir qu'un seul solde par devise
            $table->unique(['branch_id', 'currency_code']);
            
            // Foreign key vers currencies
            $table->foreign('currency_code')->references('code')->on('currencies')->onDelete('restrict');
            
            // Index pour les requêtes fréquentes
            $table->index('branch_id');
            $table->index('currency_code');
        });

        // Migration des données existantes depuis branches.cash_balance vers branch_balances
        // On suppose que le cash_balance actuel est en devise par défaut (CDF)
        DB::statement("
            INSERT INTO branch_balances (branch_id, currency_code, cash_balance, created_at, updated_at)
            SELECT 
                id, 
                'CDF',  -- Devise par défaut (à adapter selon votre configuration)
                cash_balance,
                NOW(),
                NOW()
            FROM branches
            WHERE cash_balance > 0
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_balances');
    }
};
