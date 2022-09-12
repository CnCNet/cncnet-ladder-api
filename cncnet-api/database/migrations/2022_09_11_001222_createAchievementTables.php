<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementTables extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('achievements', function (Blueprint $table)
		{
			$table->increments('id');
			$table->enum('achievement_type', ['IMMEDIATE', 'CAREER', 'MULTI']); //'career' achievements can progress and be unlocked over time, 'immediate' achievements are unlocked after one match, 'multi' achievements span over multiple ladders
			$table->integer('order')->default(999);
			$table->text('tag')->nullable(true); //can group similar achivements with a tag
			$table->integer('ladder_id')->nullable(false);
			$table->foreign('ladder_id')->references('id')->on('ladders')->onDelete('cascade');
			$table->text('achievement_name')->nullable(false);
			$table->text('achievement_description')->unique->nullable(false);
			$table->text('heap_name')->nullable(true);
			$table->text('object_name')->nullable(true);
			$table->text('cameo')->nullable(true);
			$table->integer('unlock_count')->default(0);
		});

		$this->createYRAchievements();
	}

	public function createYRAchievements()
	{
		$ladderId = \App\Ladder::where('abbreviation', 'yr')->first()->id;
		//Create YR achievements

		//create win qm games achievements
		$map[] = [
			['id' => 10, 'value' => 'Noob'],
			['id' => 25, 'value' => 'Beginner'],
			['id' => 50, 'value' => 'Beginner II'],
			['id' => 100, 'value' => 'Intemediate'],
			['id' => 300, 'value' => 'Veteran'],
			['id' => 500, 'value' => 'Warrior'],
			['id' => 1000, 'value' => 'Spartan'],
			['id' => 1500, 'value' => 'Pro'],
			['id' => 2000, 'value' => 'Master'],
			['id' => 3000, 'value' => 'Champion'] .
				['id' => 5000, 'value' => 'Legend']
		];

		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = 1;
			$a->tag = 'Win QM Games';
			$a->achievement_type = 'CAREER';
			$a->achievement_name = 'Win ' . $val . ' games';
			$a->achievement_description = 'Win ' . $key . ' YR QM Games';
			$a->unlock_count = $key;
			$a->save();
		}

		//create win Soviet qm games achievements
		$map[] = [
			['id' => 10, 'value' => 'Conscript'],
			['id' => 25, 'value' => 'Tesla Trooper'],
			['id' => 50, 'value' => 'Crazy Ivan'],
			['id' => 100, 'value' => 'Boris'],
			['id' => 300, 'value' => 'Terror Drone'],
			['id' => 500, 'value' => 'Rhino Tank'],
			['id' => 1000, 'value' => 'Tesla Tank'],
			['id' => 1500, 'value' => 'Apocalpyse Tank'],
			['id' => 2000, 'value' => 'Siege Chopper'],
			['id' => 3000, 'value' => 'Kirov']
		];

		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = 2;
			$a->tag = 'Soviet: Win QM Games';
			$a->achievement_type = 'CAREER';
			$a->achievement_name = 'Soviet wins: ' . $val;
			$a->achievement_description = 'Win ' . $key . ' YR QM Games as Soviet';
			$a->unlock_count = $key;
			$a->save();
		}

		//create win Allied qm games achievements
		$map[] = [
			['id' => 10, 'value' => 'G.I.'],
			['id' => 25, 'value' => 'Navy Seal'],
			['id' => 50, 'value' => 'Rocketeer'],
			['id' => 100, 'value' => 'Grizzly Tank'],
			['id' => 300, 'value' => 'IFV'],
			['id' => 500, 'value' => 'Mirage Tank'],
			['id' => 1000, 'value' => 'Harrier'],
			['id' => 1500, 'value' => 'Prism Tank'],
			['id' => 2000, 'value' => 'Black Eagle'],
			['id' => 3000, 'value' => 'Battle Fortress']
		];

		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = 3;
			$a->tag = 'Allied: Win QM Games';
			$a->achievement_type = 'CAREER';
			$a->achievement_name = 'Allied wins: ' . $val;
			$a->achievement_description = 'Win ' . $key . ' YR QM Games as Allied';
			$a->unlock_count = $key;
			$a->save();
		}

		//create win Yuri qm games achievements
		$map[] = [
			['id' => 10, 'value' => 'G.I.'],
			['id' => 25, 'value' => 'Navy Seal'],
			['id' => 50, 'value' => 'Rocketeer'],
			['id' => 100, 'value' => 'Grizzly Tank'],
			['id' => 300, 'value' => 'IFV'],
			['id' => 500, 'value' => 'Mirage Tank'],
			['id' => 1000, 'value' => 'Harrier'],
			['id' => 1500, 'value' => 'Prism Tank'],
			['id' => 2000, 'value' => 'Black Eagle'],
			['id' => 3000, 'value' => 'Battle Fortress']
		];

		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = 4;
			$a->tag = 'Yuri: Win QM Games';
			$a->achievement_type = 'CAREER';
			$a->achievement_name = 'Yuri wins: ' . $val;
			$a->achievement_description = 'Win ' . $key . ' YR QM Games as Yuri';
			$a->unlock_count = $key;
			$a->save();
		}


		//create play monthly qm games achievements
		$map[] = [
			['id' => 25, 'value' => 'Conscript'],
			['id' => 50, 'value' => 'Deputy'],
			['id' => 100, 'value' => 'Captain'],
			['id' => 200, 'value' => 'Lieutenant'],
			['id' => 300, 'value' => 'General'],
			['id' => 500, 'value' => 'Commander']
		];

		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = 5;
			$a->tag = 'Play Games in one Month';
			$a->achievement_type = 'CAREER';
			$a->achievement_name = 'Play YR games ' . $val;
			$a->achievement_description = 'In one month play ' . $key . ' YR QM Games';
			$a->unlock_count = $key;
			$a->save();
		}

		//create win monthly qm games achievements
		$map[] = [
			['id' => 10, 'value' => 'Rookie'],
			['id' => 30, 'value' => 'Deputy'],
			['id' => 50, 'value' => 'Captain'],
			['id' => 75, 'value' => 'Lieutenant'],
			['id' => 100, 'value' => 'General'],
			['id' => 200, 'value' => 'Commander'],
			['id' => 300, 'value' => 'Legendary']
		];

		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = 6;
			$a->tag = 'Win QM Games in One Month';
			$a->achievement_type = 'CAREER';
			$a->achievement_name = 'Win YR games ' . $val;
			$a->achievement_description = 'In one month win ' . $key . ' YR QM Games';
			$a->unlock_count = $key;
			$a->save();
		}

		//create Build Rhino Tanks QM games achievements
		$map[] = [
			['id' => 25, 'value' => 'Noob'],
			['id' => 50, 'value' => 'Veteran'],
			['id' => 100, 'value' => 'Elite']
		];

		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = 2;
			$a->tag = 'Build Rhino Tanks';
			$a->achievement_type = 'CAREER';
			$a->achievement_name = 'Rhino' . $val;
			$a->achievement_description = 'Build ' . $key . ' Rhino Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'HTNK';
			$a->cameo = 'htnkicon';
			$a->unlock_count = $key;
			$a->save();
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('achievement');
	}
}
