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
        Schema::create('kas_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kas_client_id')->constrained('kas_clients')->onDelete('cascade');

            $table->string('domain_name', 255);       // z. B. 'r3d'
            $table->string('domain_tld', 16);         // z. B. 'de'
            $table->string('domain_full', 255)->unique(); // z. B. 'r3d.de'

            $table->string('domain_path', 255)->nullable(); // /www/htdocs/w01e77bc/r3d.de/
            $table->string('php_version', 10)->default('8.1');

            $table->boolean('redirect_status')->default(false); // 0 = kein Redirect, 1 = Redirect
            $table->string('redirect_target', 255)->nullable();

            $table->enum('ssl_status', ['deaktiviert', 'aktiviert', 'letsencrypt'])->default('deaktiviert');
            $table->boolean('active')->default(true);

            $table->timestamps();
            $table->softDeletes();    // deleted_at für SoftDelete-Unterstützung
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_domains');
    }
};
