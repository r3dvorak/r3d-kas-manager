<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_action_history', function (Blueprint $table) {
            if (!Schema::hasColumn('recipe_action_history', 'success')) {
                $table->boolean('success')->default(false)->after('action_type');
            }
            if (!Schema::hasColumn('recipe_action_history', 'dry_run')) {
                $table->boolean('dry_run')->default(false)->after('success');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recipe_action_history', function (Blueprint $table) {
            if (Schema::hasColumn('recipe_action_history', 'dry_run')) {
                $table->dropColumn('dry_run');
            }
            if (Schema::hasColumn('recipe_action_history', 'success')) {
                $table->dropColumn('success');
            }
        });
    }
};
