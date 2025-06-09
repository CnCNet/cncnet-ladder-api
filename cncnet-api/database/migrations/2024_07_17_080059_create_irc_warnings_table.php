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
        Schema::connection("irc")->create('irc_warnings', function (Blueprint $table)
        {
            $table->id();
            $table->string("ident")->nullable();
            $table->string("username")->nullable();
            $table->string("channel")->nullable();
            $table->string("warning_message");
            $table->unsignedInteger("admin_id");
            $table->boolean("acknowledged")->default(false);
            $table->boolean("expired")->default(false);
            $table->timestamps();
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
