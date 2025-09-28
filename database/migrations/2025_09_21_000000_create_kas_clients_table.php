<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.7.5-alpha
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
    public function up(): void
    {
        Schema::create('kas_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('login')->unique();   // KAS Login (z. B. w01e77bc)
            $table->string('email')->nullable();
            $table->string('domain')->nullable();
            $table->string('api_user')->nullable();

            // NEU: zwei Felder für Passwort
            $table->string('password');     // bcrypt für Laravel
            $table->string('api_password'); // Klartext für SOAP

            $table->string('role')->default('client');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_clients');
    }
};
