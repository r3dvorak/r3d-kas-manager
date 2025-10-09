<?php
/**
 * R3D KAS Manager – Recipe Action History (audit)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.24.1
 * @date      2025-10-09
 * @license   MIT License
 *
 * Creates recipe_action_history table for audit/logging of recipe actions.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipe_action_history', function (Blueprint $table) {
            $table->id();

            // Optional FKs (nullable, nullOnDelete to keep history if parent removed)
            $table->foreignId('recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
            $table->foreignId('recipe_run_id')->nullable()->constrained('recipe_runs')->nullOnDelete();
            $table->foreignId('recipe_action_id')->nullable()->constrained('recipe_actions')->nullOnDelete();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kas_client_id')->nullable()->constrained('kas_clients')->nullOnDelete();

            // Quick context fields (denormalized for fast lookup)
            $table->string('kas_login')->nullable();
            $table->string('domain_name')->nullable();

            // Polymorphic / free reference to affected resource (optional)
            $table->string('affected_resource_type')->nullable(); // e.g. 'kas_dns_record','kas_mailaccount'
            $table->unsignedBigInteger('affected_resource_id')->nullable();

            // Action payloads and results
            $table->string('action_type')->nullable(); // e.g. 'add_domain','add_dns_record'
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            $table->string('status')->default('pending'); // pending, success, failed
            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            // Kurze, explizite Index-Namen (vermeidet MySQL-Längenproblem)
            $table->index(['recipe_run_id','recipe_action_id'], 'rah_run_action_idx');
            $table->index(['affected_resource_type','affected_resource_id'], 'rah_affres_idx');
            $table->index(['kas_client_id','kas_login'], 'rah_client_login_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_action_history');
    }
};
