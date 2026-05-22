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
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 100)->unique();
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->onDelete('cascade');
            $table->foreignUuid('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('Utilisateur qui a effectué l\'opération');
            $table->enum('type', ['deposit', 'withdrawal', 'interest_credit', 'interest_debit', 'fee', 'adjustment'])->comment('Type de mouvement');
            $table->decimal('amount', 18, 2)->comment('Montant du mouvement');
            $table->decimal('balance_before', 18, 2)->comment('Solde avant le mouvement');
            $table->decimal('balance_after', 18, 2)->comment('Solde après le mouvement');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['customer_account_id', 'created_at']);
            $table->index('reference');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
