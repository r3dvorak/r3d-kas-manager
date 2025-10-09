<?php
/**
 * R3D KAS Manager – Recipe Runs Table (create) with FKs
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.24.0
 * @date      2025-10-09
 * @license   MIT License
 *
 * Creates the recipe_runs table for logging executions.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipe_runs', function (Blueprint $table) {
            $table->id();

            // FK -> recipes
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();

            // FK -> users (nullable)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('pending'); // pending, running, finished, error
            $table->string('result_status')->nullable();   // success|failed|skipped

            // Context
            $table->string('kas_login')->nullable();      // optional account context (e.g. w01e77bc)
            $table->string('domain_name')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->json('variables')->nullable(); // variables for this run
            $table->json('result')->nullable(); // structured result / log

            $table->timestamps();

            $table->index(['recipe_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_runs');
    }
};
