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
        Schema::create('kas_clients', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('api_user')->unique();
        $table->string('api_password');
        $table->string('api_url')->default('https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_clients');
    }
};
