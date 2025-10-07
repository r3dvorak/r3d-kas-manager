<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('kas_clients');

        Schema::create('kas_clients', function (Blueprint $table) {
            $table->id();

            // 🔐 Auth + API
            $table->string('account_login', 20)->unique();
            $table->string('account_password', 255)->nullable(); // Encrypted KAS API password
            $table->string('password', 255)->nullable();          // Hashed Laravel login password

            // 📋 General info
            $table->string('account_comment', 255)->nullable();
            $table->string('account_contact_mail', 255)->nullable();

            // 📊 Quotas
            $table->integer('max_account')->default(0);
            $table->integer('max_domain')->default(0);
            $table->integer('max_subdomain')->default(0);
            $table->integer('max_webspace')->default(0);
            $table->integer('max_mail_account')->default(0);
            $table->integer('max_mail_forward')->default(0);
            $table->integer('max_mail_list')->default(0);
            $table->integer('max_databases')->default(0);
            $table->integer('max_ftpuser')->default(0);
            $table->integer('max_sambauser')->default(0);
            $table->integer('max_cronjobs')->default(0);
            $table->integer('max_wbk')->default(0);

            // ⚙️ Flags
            $table->char('inst_htaccess', 1)->default('N');
            $table->char('inst_fpse', 1)->default('N');
            $table->char('inst_software', 1)->default('N');
            $table->char('kas_access_forbidden', 1)->default('N');

            // 📈 Other settings
            $table->string('logging', 32)->nullable();
            $table->string('statistic', 4)->nullable();
            $table->integer('logage')->nullable();
            $table->char('show_password', 1)->default('N');
            $table->char('dns_settings', 1)->default('N');
            $table->string('show_direct_links', 8)->nullable();
            $table->char('ssh_access', 1)->default('N');
            $table->decimal('used_account_space', 12, 2)->default(0);
            $table->string('account_2fa', 16)->nullable();

            // 🧩 Direct link flags
            $table->char('show_direct_links_wbk', 1)->default('N');
            $table->char('show_direct_links_sambausers', 1)->default('N');
            $table->char('show_direct_links_accounts', 1)->default('N');
            $table->char('show_direct_links_mailaccounts', 1)->default('N');
            $table->char('show_direct_links_ftpuser', 1)->default('N');
            $table->char('show_direct_links_databases', 1)->default('N');

            // 🔄 Status
            $table->boolean('in_progress')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_clients');
    }
};
