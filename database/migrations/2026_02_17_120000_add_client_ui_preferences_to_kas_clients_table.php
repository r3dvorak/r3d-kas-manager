<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            $table->json('client_menu_items')->nullable()->after('all_inkl_contract_number');
            $table->string('preferred_locale', 8)->nullable()->after('client_menu_items');
        });
    }

    public function down(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            $table->dropColumn(['client_menu_items', 'preferred_locale']);
        });
    }
};
