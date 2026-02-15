<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            // Hex HMAC-SHA256, used only to detect whether the secret changed.
            $table->char('account_password_fingerprint', 64)->nullable()->after('account_password');
        });
    }

    public function down(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            $table->dropColumn('account_password_fingerprint');
        });
    }
};

