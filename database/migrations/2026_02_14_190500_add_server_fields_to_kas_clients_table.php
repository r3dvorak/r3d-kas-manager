<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            // Non-secret metadata for infrastructure/admin use.
            $table->string('server_internal_domain', 255)->nullable()->after('account_contact_mail'); // e.g. dd20724.srv
            $table->string('server_hostname', 255)->nullable()->after('server_internal_domain');      // e.g. w0213ab8.kasserver.com
            $table->string('server_ip', 45)->nullable()->after('server_hostname');                   // IPv4/IPv6
        });
    }

    public function down(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            $table->dropColumn(['server_internal_domain', 'server_hostname', 'server_ip']);
        });
    }
};

