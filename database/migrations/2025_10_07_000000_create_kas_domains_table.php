<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_domains', function (Blueprint $table) {
            $table->id();

            // 🔗 Relations
            $table->unsignedBigInteger('kas_client_id')->nullable()->index();
            $table->foreign('kas_client_id')->references('id')->on('kas_clients')->onDelete('cascade');


            // 🧩 Domain fields
            $table->string('domain_name', 255)->index();
            $table->string('domain_tld', 16)->nullable();
            $table->string('domain_full', 255)->nullable();
            $table->string('domain_path', 255)->nullable();
            $table->unsignedTinyInteger('domain_redirect_status')->default(0);

            // 🧱 Technical flags
            $table->char('dummy_host', 1)->default('N');
            $table->char('fpse_active', 1)->default('N');
            $table->string('dkim_selector', 128)->nullable();
            $table->string('statistic_language', 8)->nullable();
            $table->string('statistic_version', 8)->nullable();
            $table->char('ssl_proxy', 1)->default('N');
            $table->char('ssl_certificate_ip', 1)->default('N');
            $table->char('ssl_certificate_sni', 1)->default('N');
            $table->string('php_version', 8)->nullable();
            $table->char('php_deprecated', 1)->default('N');
            $table->char('is_active', 1)->default('Y');
            $table->char('in_progress', 1)->default('N');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_domains');
    }
};
