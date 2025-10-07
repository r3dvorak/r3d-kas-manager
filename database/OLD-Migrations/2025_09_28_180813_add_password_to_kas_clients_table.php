<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('kas_clients', 'password')) {
                $table->string('password')->after('api_user');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
