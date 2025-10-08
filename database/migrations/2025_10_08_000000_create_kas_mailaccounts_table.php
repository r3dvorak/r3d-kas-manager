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
        Schema::create('kas_mailaccounts', function (Blueprint $table) {
            $table->id();

            // Basic ownership
            $table->string('kas_login')->index();
            $table->string('mail_login')->index();

            // Mailbox identification
            $table->string('domain')->nullable()->index();
            $table->string('email')->nullable()->index();

            // Status: active = mailbox found, missing = empty data array
            $table->enum('status', ['active', 'missing'])->default('active')->index();

            // Raw API payload (for debugging and audits)
            $table->json('data_json')->nullable();

            // Relations to domain + client tables
            $table->unsignedBigInteger('domain_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();

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

            // Compound indexes for performance
            $table->index(['kas_login', 'mail_login']);
            $table->index(['domain', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_mailaccounts', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->dropForeign(['client_id']);
        });

        Schema::dropIfExists('kas_mailaccounts');
    }
};
