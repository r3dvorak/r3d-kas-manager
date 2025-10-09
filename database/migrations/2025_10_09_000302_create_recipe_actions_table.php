<?php
/**
 * R3D KAS Manager – Recipe Actions Table (create) with FK
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.24.0
 * @date      2025-10-09
 * @license   MIT License
 *
 * Creates the recipe_actions table which stores ordered steps for a recipe.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipe_actions', function (Blueprint $table) {
            $table->id();

            // FK -> recipes
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();

            $table->string('type'); // e.g. add_domain, apply_template, create_mailaccount, create_forwarders, enable_ssl
            $table->json('parameters')->nullable();
            $table->integer('order')->default(0);

            $table->string('label')->nullable();     // human label for UI
            $table->string('depends_on')->nullable(); // optional dependency marker (e.g. action id or type)

            $table->timestamps();

            $table->index(['recipe_id','order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_actions');
    }
};
