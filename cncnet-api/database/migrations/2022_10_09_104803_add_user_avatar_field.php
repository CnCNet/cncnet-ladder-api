<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddUserAvatarField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("users", function (Blueprint $table)
        {
            $table->string("avatar_path")->nullable();
            $table->boolean("avatar_upload_allowed")->default(true);
            $table->string("discord_profile")->nullable();
            $table->string("youtube_profile")->nullable();
            $table->string("twitch_profile")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
