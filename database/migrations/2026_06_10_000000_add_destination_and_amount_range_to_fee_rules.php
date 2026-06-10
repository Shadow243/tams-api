<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_rules', function (Blueprint $table) {
            $table->foreignId('destination_branch_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('branches')
                ->nullOnDelete();

            $table->decimal('min_amount', 15, 2)->nullable()->after('max_fee');
            $table->decimal('max_amount', 15, 2)->nullable()->after('min_amount');
        });
    }

    public function down(): void
    {
        Schema::table('fee_rules', function (Blueprint $table) {
            $table->dropForeign(['destination_branch_id']);
            $table->dropColumn(['destination_branch_id', 'min_amount', 'max_amount']);
        });
    }
};
