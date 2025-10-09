<?php
/**
 * R3D KAS Manager – Recipes Table (create) with relations
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.24.0
 * @date      2025-10-09
 * @license   MIT License
 *
 * Creates the recipes table used by the Recipe system.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_template')->default(false);

            // Optional self-reference (who/which recipe was used to create it)
            $table->unsignedBigInteger('created_from')->nullable();

            $table->integer('version')->default(1);

            // Generic metadata
            $table->string('category')->nullable(); // domain|dns|mail|ssl|composite
            // FK -> kas_templates (default/general template), nullable
            $table->foreignId('default_template_id')->nullable()->constrained('kas_templates')->nullOnDelete();
            $table->string('php_version')->nullable()->default('8.3');
            $table->boolean('enable_ssl')->default(false);
            $table->integer('expected_runtime')->nullable(); // seconds

            $table->json('variables')->nullable(); // template variables / defaults
            $table->timestamps();

            $table->index(['is_template', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
