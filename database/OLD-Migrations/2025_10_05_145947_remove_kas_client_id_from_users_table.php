<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['kas_client_id']);
            $table->dropColumn('kas_client_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kas_client_id')->nullable()->constrained('kas_clients');
        });
    }

};
