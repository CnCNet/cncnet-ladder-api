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
        Schema::connection("irc")->create('irc_messages', function (Blueprint $table)
        {
            $table->id();
            $table->string("ident");
            $table->string("message");
            $table->string("host");
            $table->string("username");
            $table->string("channel");
            $table->string("client");
            $table->timestamp("message_created"); // Bot will tell us when it syncs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('irc_bans');
    }
};
