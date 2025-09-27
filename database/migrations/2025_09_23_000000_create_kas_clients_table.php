<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.7.4-alpha
 * @date      2025-09-23
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * database\migrations\2025_09_23_000000_create_kas_clients_table.php
 */

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

            // Anzeigename
            $table->string('name');

            // Login ist Pflicht und eindeutig (z. B. w01e77bc)
            $table->string('login')->unique();

            // Email optional
            $table->string('email')->nullable()->index();

            // Domain (z. B. ord.de)
            $table->string('domain')->nullable();

            // API-Zugangsdaten (optional)
            $table->string('api_user')->nullable();
            $table->string('api_password')->nullable();

            // Rolle für spätere Erweiterung (default client)
            $table->string('role')->default('client');

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
