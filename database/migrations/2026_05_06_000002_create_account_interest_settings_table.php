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
        Schema::create('account_interest_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->onDelete('cascade');
            $table->enum('interest_type', ['percentage', 'fixed'])->comment('Type d\'intérêt: pourcentage ou montant fixe');
            $table->decimal('interest_rate', 8, 4)->nullable()->comment('Taux d\'intérêt en % (ex: 5.5 pour 5.5%)');
            $table->decimal('fixed_amount', 18, 2)->nullable()->comment('Montant fixe d\'intérêt');
            $table->enum('application_period', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->boolean('apply_on_negative_balance')->default(true)->comment('Appliquer les intérêts sur solde négatif (prêt)');
            $table->boolean('apply_on_positive_balance')->default(false)->comment('Appliquer les intérêts sur solde positif (épargne)');
            $table->timestamp('last_applied_at')->nullable();
            $table->timestamp('next_application_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['customer_account_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_interest_settings');
    }
};
