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
        Schema::dropIfExists('player_invitations');
        Schema::create('player_invitations', function (Blueprint $table)
        {
            $table->id();
            $table->unsignedInteger('author_player_id');
            $table->unsignedInteger('invited_player_id');
            $table->enum('status', ['accepted', 'pending', 'declined', 'canceled']);
            $table->timestamps();

            $table->foreign('author_player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('invited_player_id')->references('id')->on('players')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
