<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditUsersTable extends Migration 
{
	public function up()
	{
		Schema::table('users', function(Blueprint $table)
		{
            $table->enum('group', ['User', 'Moderator', 'Admin', 'God'])->default('User');
		});
    }

	public function down()
	{

	}
}
