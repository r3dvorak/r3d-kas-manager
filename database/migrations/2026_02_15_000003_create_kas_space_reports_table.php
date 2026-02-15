<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_space_reports', function (Blueprint $table) {
            $table->id();

            $table->string('kas_login')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            $table->timestamp('measured_at')->nullable()->index();
            $table->bigInteger('used_kb')->nullable();
            $table->bigInteger('max_kb')->nullable();

            $table->json('data_json')->nullable();
            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('kas_clients')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_space_reports');
    }
};

