<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.5.0-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license     MIT License
 * 
 * Table for storing KAS clients (login + password + admin flag).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kas_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kas_login')->unique();
            $table->string('kas_auth_data');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client'); // 'admin' or 'client'
            $table->foreignId('kas_client_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kas_client_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('kas_clients');
    }
};
