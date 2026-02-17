<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('recipes')) {
            return;
        }

        Schema::table('recipes', function (Blueprint $table) {
            if (!Schema::hasColumn('recipes', 'status')) {
                $table->string('status', 24)->default('draft')->after('version');
                $table->index('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('recipes')) {
            return;
        }

        Schema::table('recipes', function (Blueprint $table) {
            if (Schema::hasColumn('recipes', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};

