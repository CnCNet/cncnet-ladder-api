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
        Schema::connection("irc")->create('irc_ban_logs', function (Blueprint $table)
        {
            $table->id();
            $table->unsignedInteger("ban_id");
            $table->unsignedBigInteger("admin_id");
            $table->string("action");
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
