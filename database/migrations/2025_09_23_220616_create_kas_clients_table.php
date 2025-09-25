<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void 
    {
        Schema::create('kas_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // Anzeigename
            $table->string('login')->unique();    // API-User oder KAS-Login (z. B. w01e77bc)
            $table->string('domain')->nullable(); // Eine Domain als Referenz
            $table->string('api_password');       // Passwort
            $table->string('api_url')->default('https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_clients');
    }
};
