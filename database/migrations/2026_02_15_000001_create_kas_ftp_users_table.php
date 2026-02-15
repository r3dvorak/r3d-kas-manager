<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_ftp_users', function (Blueprint $table) {
            $table->id();

            $table->string('kas_login')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            $table->string('ftp_login')->nullable()->index();
            $table->string('ftp_path')->nullable();
            $table->string('ftp_comment')->nullable();

            // Permissions (KAS usually uses Y/N flags)
            $table->char('perm_read', 1)->default('N');
            $table->char('perm_write', 1)->default('N');
            $table->char('perm_list', 1)->default('N');

            $table->enum('status', ['active', 'missing'])->default('active')->index();
            $table->json('data_json')->nullable();

            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('kas_clients')
                ->onDelete('set null');

            $table->index(['kas_login', 'ftp_login']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_ftp_users');
    }
};

