<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R3D KAS Manager – create_kas_subdomains_table
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.17.3-alpha
 * @date      2025-10-07
 * @license   MIT License
 */

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kas_subdomains', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('kas_client_id')
                ->constrained('kas_clients')
                ->onDelete('cascade');

            $table->foreignId('domain_id')
                ->nullable()
                ->constrained('kas_domains')
                ->onDelete('cascade');

            // Core subdomain fields
            $table->string('subdomain_name');   // e.g. "blog"
            $table->string('subdomain_full');   // e.g. "blog.example.com"
            $table->string('subdomain_path')->nullable(); // e.g. "/web/blog/"
            $table->string('php_version')->nullable();    // e.g. "8.3"
            $table->boolean('ssl_status')->default(false);
            $table->boolean('active')->default(true);
            $table->boolean('redirect_status')->default(false);
            $table->string('redirect_target')->nullable();

            // Extra metadata from API
            $table->string('dkim_selector')->nullable();
            $table->string('dummy_host')->nullable();
            $table->string('fpse_active')->nullable();
            $table->string('ssl_certificate_sni')->nullable();
            $table->string('ssl_certificate_sni_is_active')->nullable();
            $table->boolean('php_deprecated')->default(false);
            $table->boolean('in_progress')->default(false);

            // Timestamps + soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('subdomain_full');
            $table->index('kas_client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_subdomains');
    }
};
