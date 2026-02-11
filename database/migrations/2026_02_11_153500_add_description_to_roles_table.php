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
        $tableNames = config('permission.table_names');
        
        throw_if(empty($tableNames), Exception::class, 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->string('group')->nullable()->after('name');
            $table->string('module_name')->nullable()->after('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        
        throw_if(empty($tableNames), Exception::class, 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->dropColumn('description');
        });
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->dropColumn('group');
            $table->dropColumn('module_name');
        });
    }
};
