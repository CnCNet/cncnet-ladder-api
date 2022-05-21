<?php

use App\Models\Ladder;
use App\Models\LadderAdmin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdminDevo extends Migration
{

	/**
	 * Run the migrations.
	 * Set devo's user as an admin and make him a ladder admin for the YR test ladder.
	 *
	 * @return void
	 */
	public function up()
	{
		$user = User::where('name', 'devo1929')->first();
		$user->group = 'Admin';
		$user->save();

		$privateLadder = Ladder::where('abbreviation', 'yr-test')->first();

		if (isset($privateLadder))
		{
			$ladderAdmin = new LadderAdmin();
			$ladderAdmin->user_id = $user->id;
			$ladderAdmin->ladder_id = $privateLadder->id;
			$ladderAdmin->admin = 1;
			$ladderAdmin->moderator = 1;
			$ladderAdmin->tester = 1;
			$ladderAdmin->save();
		}
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
