<?php
/**
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.7.4-alpha
 * @date      2025-09-24
 *
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 *
 * database\migrations\2025_09_24_050009_create_recipe_actions_table.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipe_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('parameters')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_actions');
    }
};
