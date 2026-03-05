<?php

declare(strict_types=1);

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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique()->index();
            
            // Foreign Keys
            $table->foreignId('transaction_type_id')->constrained('transaction_types')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('destination_branch_id')->nullable()->constrained('branches')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->onDelete('restrict');
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->onDelete('restrict');
            
            // Customer Info
            $table->string('customer_phone', 20)->nullable()->index();
            
            // Amounts
            $table->decimal('gross_amount', 18, 2);
            $table->decimal('fee_amount', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2);
            
            // Fee Information
            $table->foreignId('fee_rule_id')->nullable()->constrained('fee_rules')->onDelete('set null');
            $table->enum('fee_mode_applied', ['fixed', 'percentage', 'negotiated', 'manual_override']);
            $table->json('fee_snapshot')->nullable()->comment('Snapshot of fee rule at transaction time');
            
            // Transaction Relationships
            $table->foreignUuid('parent_transaction_id')->nullable()->constrained('transactions')->onDelete('restrict');
            
            // Withdrawal Info
            $table->string('withdrawal_code', 20)->nullable()->unique()->index();
            $table->timestamp('expires_at')->nullable()->index();
            
            // Status
            $table->enum('status', ['pending', 'available', 'completed', 'cancelled', 'failed', 'expired'])
                ->default('pending')
                ->index();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['branch_id', 'status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['customer_phone', 'created_at']);
            $table->index(['transaction_type_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
