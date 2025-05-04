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
        Schema::connection("irc")->create('irc_ip_addresses', function (Blueprint $table)
        {
            $table->id();
            $table->string("address");
            $table->string("city");
            $table->string("country");
            $table->timestamps();
        });

        Schema::connection("irc")->create('irc_ip_addresses_histories', function (Blueprint $table)
        {
            $table->id();
            $table->unsignedInteger("irc_user_id");
            $table->string("ip_address_id");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
