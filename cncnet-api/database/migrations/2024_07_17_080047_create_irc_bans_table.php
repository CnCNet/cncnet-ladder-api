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
        Schema::connection("irc")->create('irc_bans', function (Blueprint $table)
        {
            $table->id();
            $table->string("ident")->nullable();
            $table->string("host")->nullable();
            $table->string("username")->nullable();
            $table->string("channel")->nullable();
            $table->boolean("global_ban")->default(false);
            $table->string("ban_reason");
            $table->unsignedInteger("admin_id");
            $table->timestamp("ban_original_expiry")->nullable(); // Keep as a reference
            $table->timestamp("expires_at")->nullable();
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
