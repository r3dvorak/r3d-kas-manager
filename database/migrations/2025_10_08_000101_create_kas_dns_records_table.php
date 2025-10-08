<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_dns_records', function (Blueprint $table) {
            $table->id();
            $table->string('kas_login', 32)->nullable()->index();
            $table->foreignId('domain_id')->nullable()->constrained('kas_domains')->onDelete('cascade');

            $table->string('record_zone', 255)->nullable()->index();
            $table->string('record_name', 255)->nullable();
            $table->string('record_type', 16)->nullable();
            $table->text('record_data')->nullable();
            $table->integer('record_aux')->default(0);

            $table->string('record_id_kas', 32)->nullable()->index();
            $table->char('record_changeable', 1)->default('Y');
            $table->char('record_deleteable', 1)->default('Y');

            $table->longText('data_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_dns_records');
    }
};
