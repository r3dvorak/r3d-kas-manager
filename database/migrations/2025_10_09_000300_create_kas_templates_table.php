<?php
/**
 * R3D KAS Manager – Kas Templates (generic)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.24.0
 * @date      2025-10-09
 * @license   MIT License
 *
 * Generic template table for DNS / Mail / PHP / SSL / DKIM / SPF etc.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kas_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_type', 64); // dns, mail, php, ssl, dkim, spf, app, etc.
            $table->string('template_name', 120);
            $table->text('description')->nullable();
            $table->json('data_json'); // arbitrary structure depending on template_type
            $table->foreignId('kas_client_id')->nullable()->constrained('kas_clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['template_type','template_name'], 'kas_template_type_name_idx');
            $table->index(['template_type','kas_client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_templates');
    }
};
