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
        Schema::create('kas_subdomains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kas_client_id')->constrained('kas_clients')->onDelete('cascade');
            $table->foreignId('domain_id')->constrained('kas_domains')->onDelete('cascade');

            $table->string('subdomain_name', 255);      // z. B. 'mail', 'test'
            $table->string('subdomain_full', 255)->unique(); // z. B. 'mail.r3d.de'

            $table->string('subdomain_path', 255)->nullable(); // z. B. '/www/htdocs/w01e77bc/mail.r3d.de/'
            $table->string('php_version', 10)->default('8.1');

            $table->boolean('redirect_status')->default(false);
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
        Schema::dropIfExists('kas_subdomains');
    }
};
