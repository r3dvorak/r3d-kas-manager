<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_launch_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_launch_token_id')->nullable()->constrained('external_launch_tokens')->nullOnDelete();
            $table->foreignId('kas_client_id')->nullable()->constrained('kas_clients')->nullOnDelete();
            $table->string('event', 32);
            $table->string('tool', 32)->nullable();
            $table->string('workspace_key', 40)->nullable();
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_launch_audits');
    }
};
