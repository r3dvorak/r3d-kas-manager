<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_databases', function (Blueprint $table) {
            $table->id();

            $table->string('kas_login')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            $table->string('database_login')->nullable()->index();
            $table->string('database_comment')->nullable();
            $table->text('database_allowed_hosts')->nullable();

            $table->enum('status', ['active', 'missing'])->default('active')->index();
            $table->json('data_json')->nullable();

            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('kas_clients')
                ->onDelete('set null');

            $table->index(['kas_login', 'database_login']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_databases');
    }
};

