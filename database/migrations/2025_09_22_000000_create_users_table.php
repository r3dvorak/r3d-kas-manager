<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.7.4-alpha
 * @date      2025-09-22
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * database\migrations\2025_09_22_000000_create_users_table.php
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
        // === USERS ===
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Benutzername / Anzeige
            $table->string('name');

            // Login ist Pflicht & eindeutig
            $table->string('login')->unique();

            // Email optional (kein Unique mehr)
            $table->string('email')->nullable()->index();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Rollen & Flags
            $table->string('role')->default('user');
            $table->boolean('is_admin')->default(false);

            // FK auf kas_clients (optional, wenn ein User einem Client zugeordnet ist)
            $table->foreignId('kas_client_id')
                  ->nullable()
                  ->constrained('kas_clients')
                  ->cascadeOnDelete();

            $table->rememberToken();
            $table->timestamps();
        });

        // === PASSWORD RESETS ===
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // === SESSIONS ===
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            // String statt bigint → erlaubt IDs von User + KasClient (login z. B. w01e77bc)
            $table->string('user_id', 255)->nullable()->index();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
