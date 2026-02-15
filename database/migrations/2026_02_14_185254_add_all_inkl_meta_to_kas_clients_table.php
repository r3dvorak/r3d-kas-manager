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
        Schema::table('kas_clients', function (Blueprint $table) {
            $table->string('all_inkl_customer_number', 32)->nullable()->after('server_ip');  // e.g. 858656
            $table->string('all_inkl_contract_number', 32)->nullable()->after('all_inkl_customer_number'); // e.g. 1822536
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_clients', function (Blueprint $table) {
            $table->dropColumn(['all_inkl_customer_number', 'all_inkl_contract_number']);
        });
    }
};
