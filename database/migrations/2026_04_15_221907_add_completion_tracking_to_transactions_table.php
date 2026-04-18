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
        Schema::table('transactions', function (Blueprint $table) {
            // Tracking qui a complété la transaction
            $table->foreignId('completed_by')->nullable()->after('user_id')->constrained('users')->onDelete('restrict');
            
            // Tracking quelle branche a servi le client
            $table->foreignId('served_by_branch_id')->nullable()->after('destination_branch_id')->constrained('branches')->onDelete('restrict');
            
            // Date et heure de complétion
            $table->timestamp('completed_at')->nullable()->after('expires_at');
            
            // Index pour les rapports
            $table->index(['completed_by', 'completed_at']);
            $table->index(['served_by_branch_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['served_by_branch_id']);
            $table->dropIndex(['completed_by', 'completed_at']);
            $table->dropIndex(['served_by_branch_id', 'completed_at']);
            $table->dropColumn(['completed_by', 'served_by_branch_id', 'completed_at']);
        });
    }
};
