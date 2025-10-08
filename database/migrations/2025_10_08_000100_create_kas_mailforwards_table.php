<?php

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
        Schema::create('kas_mailforwards', function (Blueprint $table) {
            $table->id();

            // Basic ownership
            $table->string('kas_login')->index();

            // Forwarder identification
            $table->string('mail_forward_address')->nullable()->index();
            $table->text('mail_forward_targets')->nullable();

            // Additional metadata
            $table->string('mail_forward_comment')->nullable();
            $table->string('mail_forward_spamfilter')->nullable();
            $table->boolean('in_progress')->default(false);
            $table->enum('status', ['active', 'missing'])->default('active')->index();

            // Relations to domain + client tables
            $table->unsignedBigInteger('domain_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // Full API payload for debugging
            $table->json('data_json')->nullable();

            $table->timestamps();

            // Foreign keys (update-safe)
            $table->foreign('domain_id')
                  ->references('id')
                  ->on('kas_domains')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            $table->foreign('client_id')
                  ->references('id')
                  ->on('kas_clients')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            // Useful combined indexes
            $table->index(['kas_login', 'mail_forward_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_mailforwards', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->dropForeign(['client_id']);
        });

        Schema::dropIfExists('kas_mailforwards');
    }
};
