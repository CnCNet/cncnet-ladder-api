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
			$table->text('tag')->nullable(true); //can group similar achievements with a tag
			$table->integer('ladder_id')->unsigned();
			$table->foreign('ladder_id')->references('id')->on('ladders')->onDelete('cascade');
			$table->text('achievement_name')->nullable(false);
			$table->text('achievement_description')->nullable(false);
			$table->text('heap_name')->nullable(true);
			$table->text('object_name')->nullable(true);
			$table->text('cameo')->nullable(true);
			$table->integer('unlock_count')->default(0);
		});

		$this->createAchievements();
	}

	/**
	 * Create Achievements
	 */
	private function createAchievements()
	{
		//create achievements for these ladders
		$lmap = [
			1 => 'Yuri\'s Revenge',
			2 => 'Tiberian Sun',
			3 => 'Red Alert',
			5 => 'Red Alert 2',
			8 => 'Blitz'
		];
		$order = 0;
		$type = 'CAREER';

		foreach ($lmap as $ladderId => $ladderName)
		{
			//create win qm games achievements
			$map = [
				10 => 'Noob',
				25 => 'Recruit',
				50 => 'Soldier',
				100 => 'Marine',
				300 => 'Veteran',
				500 => 'Warrior',
				1000 => 'Spartan',
				1500 => 'Pro',
				2000 => 'Master',
				3000 => 'Champion',
				5000 => 'Legend'
			];
			foreach ($map as $numGames => $achName)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Win ' . $ladderName . ' QM Games';
				$a->achievement_type = $type;
				$a->achievement_name = $ladderName . ' ' . $achName;
				$a->achievement_description = 'Win ' . $numGames . ' ' . $ladderName . ' QM Games';
				$a->unlock_count = $numGames;
				$a->save();
			}

			//create win qm games as faction achievements
			$arr = ['Soviet', 'Allied', 'Yuri'];
			foreach ($arr as $faction)
			{
				$map = [
					10 => $faction . ' Noob',
					25 => $faction . ' Recruit',
					50 => $faction . ' Soldier',
					100 => $faction .  ' Marine',
					300 => $faction . ' Veteran',
					500 => $faction . ' Warrior',
					1000 => $faction .  ' Spartan',
					1500 => $faction . ' Master',
					2000 => $faction . ' Champion',
					3000 => $faction . ' Legend'
				];

				//create the achievement
				foreach ($map as $numGames => $achName)
				{
					$a = new \App\Achievement();
					$a->ladder_id = $ladderId;
					$a->order = $order++;
					$a->tag = explode(" ", $achName)[0] . ': Win QM Games';
					$a->achievement_type = $type;
					$a->achievement_name = $ladderName . ' ' . $achName;
					$a->achievement_description = 'Win ' . $numGames . ' ' . $ladderName . ' QM Games as ' . explode(" ", $achName)[0];
					$a->unlock_count = $numGames;
					$a->save();
				}
			}
		}

		//create play monthly qm games achievements
		$map = [
			25 => 'Conscript',
			50 => 'Deputy',
			100 => 'Captain',
			200 => 'Lieutenant',
			300 => 'General',
			500 => 'Commander'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Play Games in one Month';
			$a->achievement_type = $type;
			$a->achievement_name = 'Play YR games ' . $val;
			$a->achievement_description = 'In one month play ' . $key . ' YR QM Games';
			$a->unlock_count = $key;
			$a->save();
		}

		//create win monthly qm games achievements
		$map = [
			10 => 'Rookie',
			30 => 'Deputy',
			50 => 'Captain',
			75 => 'Lieutenant',
			100 => 'General',
			200 => 'Commander',
			300 => 'Legendary'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Win QM Games in One Month';
			$a->achievement_type = $type;
			$a->achievement_name = 'Win YR games ' . $val;
			$a->achievement_description = 'In one month win ' . $key . ' YR QM Games';
			$a->unlock_count = $key;
			$a->save();
		}

		$arr = [1, 5, 8]; //yr, ra2, blitz ladders
		foreach ($arr as $i)
		{
			$order = $this->sovietCareerBuild($i, $order);
			$order = $this->sovietIntermediateBuild($i, $order);
			$order = $this->alliedCareerBuild($i, $order);
			$order = $this->alliedIntermediateBuild($i, $order);

			if ($i !== 5 && $i !== 8) //yr achievemnts only for yuri ladder
			{
				$order = $this->yuriCareerBuild($i, $order);
				$order = $this->yuriIntermediateBuild($i, $order);
			}
		}
	}

	/**
	 * Career Achievements for building soviet units
	 */
	private function sovietCareerBuild($ladderId, $order)
	{
		$type = 'CAREER';

		//Build Conscripts achievements
		$map = [
			50 => 'Noob',
			100 => 'Recruit',
			500 => 'Captain',
			1000 => 'Veteran',
			2000 => 'Master',
			3000 => 'Legend',
			5000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Conscripts';
			$a->achievement_type = $type;
			$a->achievement_name = 'Conscript ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Conscripts';
			$a->heap_name = 'UNB';
			$a->object_name = 'E2';
			$a->cameo = 'e2icon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Soviet Dogs achievements
		$map = [
			50 => 'Noob',
			100 => 'Recruit',
			500 => 'Captain',
			1000 => 'Veteran',
			2000 => 'Master',
			3000 => 'Legend',
			5000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Soviet Attack Dogs';
			$a->achievement_type = $type;
			$a->achievement_name = 'Attack Dog ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Attack Dogs';
			$a->heap_name = 'UNB';
			$a->object_name = 'DOG';
			$a->cameo = 'dogicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Flak Troopers achievements
		$map = [
			10 => 'Noob',
			30 => 'Recruit',
			60 => 'Captain',
			125 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Flak Troopers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Flak Troopers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Flak Troopers';
			$a->heap_name = 'UNB';
			$a->object_name = 'FLKT';
			$a->cameo = 'flkticon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Tesla Troopers achievements
		$map = [
			10 => 'Noob',
			30 => 'Recruit',
			60 => 'Captain',
			125 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Tesla Troopers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Tesla Troopers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Tesla Troopers';
			$a->heap_name = 'UNB';
			$a->object_name = 'SHK';
			$a->cameo = 'shkticon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Crazy Ivans achievements
		$map = [
			5 => 'Noob',
			15 => 'Recruit',
			30 => 'Captain',
			60 => 'Veteran',
			100 => 'Master',
			300 => 'Legend',
			500 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Crazy Ivans';
			$a->achievement_type = $type;
			$a->achievement_name = 'Crazy Ivans ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Crazy Ivans';
			$a->heap_name = 'UNB';
			$a->object_name = 'IVAN';
			$a->cameo = 'ivanicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Desolators achievements
		$map = [
			10 => 'Noob',
			30 => 'Recruit',
			75 => 'Captain',
			150 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Desolators';
			$a->achievement_type = $type;
			$a->achievement_name = 'Desos ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Desolators';
			$a->heap_name = 'UNB';
			$a->object_name = 'DESO';
			$a->cameo = 'desoicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Terrorists achievements
		$map = [
			10 => 'Noob',
			30 => 'Recruit',
			75 => 'Captain',
			150 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Terrorists';
			$a->achievement_type = $type;
			$a->achievement_name = 'Terrorists ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Terrorists';
			$a->heap_name = 'UNB';
			$a->object_name = 'TERROR';
			$a->cameo = 'terroricon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Boris achievements
			$map = [
				3 => 'Noob',
				10 => 'Recruit',
				25 => 'Captain',
				50 => 'Veteran',
				100 => 'Master',
				200 => 'Legend',
				300 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Boris Desolators';
				$a->achievement_type = $type;
				$a->achievement_name = 'Boris ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Boris';
				$a->heap_name = 'UNB';
				$a->object_name = 'BORIS';
				$a->cameo = 'brisicon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Rhino Tanks achievements
		$map = [
			50 => 'Noob',
			100 => 'Recruit',
			500 => 'Captain',
			1000 => 'Veteran',
			2000 => 'Master',
			3000 => 'Legend',
			5000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Rhino Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Rhino ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Rhino Tanks';
			$a->heap_name = 'UNB';
			$a->object_name = 'HTNK';
			$a->cameo = 'htnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build flak traks achievements
		$map = [
			20 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			200 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Flak Traks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Flak Traks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Flak Traks';
			$a->heap_name = 'UNB';
			$a->object_name = 'HTK';
			$a->cameo = 'htkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Terror Drones achievements
		$map = [
			20 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			200 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Terror Drones';
			$a->achievement_type = $type;
			$a->achievement_name = 'Terror Drones ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Terror Drones';
			$a->heap_name = 'UNB';
			$a->object_name = 'DRON';
			$a->cameo = 'dronicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Tesla Tanks achievements
		$map = [
			10 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			250 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Tesla Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Tesla Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Tesla Tanks';
			$a->heap_name = 'UNB';
			$a->object_name = 'TTNK';
			$a->cameo = 'ttnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Demolition trucks achievements
		$map = [
			3 => 'Noob',
			10 => 'Recruit',
			30 => 'Captain',
			50 => 'Veteran',
			100 => 'Master',
			200 => 'Legend',
			300 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Demolition Trucks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Demolition Trucks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Demolition Trucks';
			$a->heap_name = 'UNB';
			$a->object_name = 'DTRUCK';
			$a->cameo = 'dtruckicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Apocalypse Tanks achievements
		$map = [
			10 => 'Noob',
			30 => 'Recruit',
			60 => 'Captain',
			125 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Apocalypse Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Apocalypse Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Apocalypse Tanks';
			$a->heap_name = 'UNB';
			$a->object_name = 'APOC';
			$a->cameo = 'mtnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Siege Choppers achievements
			$map = [
				10 => 'Noob',
				30 => 'Recruit',
				60 => 'Captain',
				125 => 'Veteran',
				300 => 'Master',
				500 => 'Legend',
				1000 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Build Siege Choppers';
				$a->achievement_type = $type;
				$a->achievement_name = 'Siege Choppers ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Siege Choppers';
				$a->heap_name = 'UNB';
				$a->object_name = 'SCHP';
				$a->cameo = 'schpicon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Kirovs achievements
		$map = [
			5 => 'Noob',
			20 => 'Recruit',
			50 => 'Captain',
			100 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Kirovs';
			$a->achievement_type = $type;
			$a->achievement_name = 'Kirovs ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Kirovs in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'ZEP';
			$a->cameo = 'zepicon';
			$a->unlock_count = $key;
			$a->save();
		}

		return $order;
	}

	/**
	 * Intermediate Achievements for building soviet units
	 */
	private function sovietIntermediateBuild($ladderId, $order)
	{
		$type = 'INTERMEDIATE';

		//Build Conscripts achievements
		$map = [
			25 => 'Noob',
			50 => 'Veteran',
			100 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Conscripts';
			$a->achievement_type = $type;
			$a->achievement_name = 'Conscript ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Conscripts in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'E2';
			$a->cameo = 'e2icon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Soviet Dogs achievements
		$map = [
			25 => 'Noob',
			50 => 'Veteran',
			100 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Soviet Attack Dogs';
			$a->achievement_type = $type;
			$a->achievement_name = 'Attack Dog ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Attack Dogs in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'DOG';
			$a->cameo = 'dogicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Flak Troopers achievements
		$map = [
			15 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Flak Troopers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Flak Troopers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Flak Troopers in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'FLKT';
			$a->cameo = 'flkticon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Tesla Troopers achievements
		$map = [
			10 => 'Noob',
			25 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Tesla Troopers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Tesla Troopers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Tesla Troopers in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'SHK';
			$a->cameo = 'shkticon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Crazy Ivans achievements
		$map = [
			5 => 'Noob',
			15 => 'Veteran',
			30 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Crazy Ivans';
			$a->achievement_type = $type;
			$a->achievement_name = 'Crazy Ivans ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Crazy Ivans in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'IVAN';
			$a->cameo = 'ivanicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Desolators achievements
		$map = [
			10 => 'Noob',
			25 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Desolators';
			$a->achievement_type = $type;
			$a->achievement_name = 'Desos ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Desolators in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'DESO';
			$a->cameo = 'desoicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Terrorists achievements
		$map = [
			10 => 'Noob',
			25 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Terrorists';
			$a->achievement_type = $type;
			$a->achievement_name = 'Terrorists ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Terrorists in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'TERROR';
			$a->cameo = 'terroricon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Rhino Tanks achievements
		$map = [
			20 => 'Noob',
			50 => 'Veteran',
			100 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Rhino Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Rhino ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Rhino Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'HTNK';
			$a->cameo = 'htnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build flak traks achievements
		$map = [
			15 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Flak Traks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Flak Traks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Flak Traks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'HTK';
			$a->cameo = 'htkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Terror Drones achievements
		$map = [
			15 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Terror Drones';
			$a->achievement_type = $type;
			$a->achievement_name = 'Terror Drones ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Terror Drones  in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'DRON';
			$a->cameo = 'dronicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Tesla Tanks achievements
		$map = [
			15 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Tesla Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Tesla Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Tesla Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'TTNK';
			$a->cameo = 'ttnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Demolition trucks achievements
		$map = [
			5 => 'Noob',
			15 => 'Veteran',
			40 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Demolition Trucks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Demolition Trucks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Demolition Trucks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'DTRUCK';
			$a->cameo = 'dtruckicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Apocalypse Tanks achievements
		$map = [
			15 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Apocalypse Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Apocalypse Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Apocalypse Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'APOC';
			$a->cameo = 'mtnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Siege Choppers achievements
			$map = [
				15 => 'Noob',
				30 => 'Veteran',
				60 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Build Siege Choppers';
				$a->achievement_type = $type;
				$a->achievement_name = 'Siege Choppers ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Siege Choppers in one game';
				$a->heap_name = 'UNB';
				$a->object_name = 'SCHP';
				$a->cameo = 'schpicon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Kirovs achievements
		$map = [
			5 => 'Noob',
			15 => 'Veteran',
			30 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Kirovs';
			$a->achievement_type = $type;
			$a->achievement_name = 'Kirovs ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Kirovs in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'ZEP';
			$a->cameo = 'zepicon';
			$a->unlock_count = $key;
			$a->save();
		}

		return $order;
	}

	/**
	 * Career Achievements for building allied units
	 */
	private function alliedCareerBuild($ladderId, $order)
	{
		$type = 'CAREER';

		//Build G.I. achievements
		$map = [
			50 => 'Noob',
			100 => 'Recruit',
			500 => 'Captain',
			1000 => 'Veteran',
			2000 => 'Master',
			3000 => 'Legend',
			5000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build GIs';
			$a->achievement_type = $type;
			$a->achievement_name = 'GI ' . $val;
			$a->achievement_description = 'Build ' . $key . ' GIs';
			$a->heap_name = 'UNB';
			$a->object_name = 'E1';
			$a->cameo = 'e1icon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Allied Dogs achievements
		$map = [
			50 => 'Noob',
			100 => 'Recruit',
			250 => 'Captain',
			500 => 'Veteran',
			1000 => 'Master',
			2000 => 'Legend',
			3000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Allied Attack Dogs';
			$a->achievement_type = $type;
			$a->achievement_name = 'Attack Dog ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Attack Dogs';
			$a->heap_name = 'UNB';
			$a->object_name = 'ADOG';
			$a->cameo = 'adogicon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Guardian GIs achievements
			$map = [
				10 => 'Noob',
				30 => 'Recruit',
				60 => 'Captain',
				125 => 'Veteran',
				300 => 'Master',
				500 => 'Legend',
				1000 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Build Guardian GIs';
				$a->achievement_type = $type;
				$a->achievement_name = 'Guardian GIs ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Guardian GIs';
				$a->heap_name = 'UNB';
				$a->object_name = 'GGI';
				$a->cameo = 'gdgiicon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Snipers achievements
		$map = [
			5 => 'Noob',
			25 => 'Recruit',
			50 => 'Captain',
			75 => 'Veteran',
			150 => 'Master',
			300 => 'Legend',
			500 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Snipers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Snipers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Snipers';
			$a->heap_name = 'UNB';
			$a->object_name = 'SNIPE';
			$a->cameo = 'snipicon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Navy Seal achievements
			$map = [
				5 => 'Noob',
				15 => 'Recruit',
				30 => 'Captain',
				60 => 'Veteran',
				100 => 'Master',
				300 => 'Legend',
				500 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Build Navy Seals';
				$a->achievement_type = $type;
				$a->achievement_name = 'Navy Seals ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Navy Seals';
				$a->heap_name = 'UNB';
				$a->object_name = 'GHOST';
				$a->cameo = 'gosticon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Rocketeers achievements
		$map = [
			20 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			300 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Rocketeers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Rocketeers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Rocketeers';
			$a->heap_name = 'UNB';
			$a->object_name = 'JUMPJET';
			$a->cameo = 'jjeticon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Chrono Legionnaires achievements
		$map = [
			3 => 'Noob',
			10 => 'Recruit',
			30 => 'Captain',
			60 => 'Veteran',
			100 => 'Master',
			300 => 'Legend',
			500 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Chrono Legionnaires';
			$a->achievement_type = $type;
			$a->achievement_name = 'Chrono Legionnaires ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Chrono Legionnaires';
			$a->heap_name = 'UNB';
			$a->object_name = 'CLEG';
			$a->cameo = 'clegicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Tanya achievements
		$map = [
			3 => 'Noob',
			10 => 'Recruit',
			25 => 'Captain',
			50 => 'Veteran',
			100 => 'Master',
			200 => 'Legend',
			300 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Boris Tanyas';
			$a->achievement_type = $type;
			$a->achievement_name = 'Tanyas ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Tanyas';
			$a->heap_name = 'UNB';
			$a->object_name = 'TANY';
			$a->cameo = 'tanyicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Grizzly Tanks achievements
		$map = [
			50 => 'Noob',
			100 => 'Recruit',
			300 => 'Captain',
			500 => 'Veteran',
			1000 => 'Master',
			2000 => 'Legend',
			3000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Grizzly Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Grizzly ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Grizzly Tanks';
			$a->heap_name = 'UNB';
			$a->object_name = 'MTNK';
			$a->cameo = 'gtnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build IFVs achievements
		$map = [
			20 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			200 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build IFVs';
			$a->achievement_type = $type;
			$a->achievement_name = 'IFVs ' . $val;
			$a->achievement_description = 'Build ' . $key . ' IFVs';
			$a->heap_name = 'UNB';
			$a->object_name = 'FV';
			$a->cameo = 'fvicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Mirage Tanks achievements
		$map = [
			20 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			200 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Mirage Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Mirage Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Mirage Tanks';
			$a->heap_name = 'UNB';
			$a->object_name = 'MGTK';
			$a->cameo = 'rtnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Prism Tanks achievements
		$map = [
			10 => 'Noob',
			30 => 'Recruit',
			75 => 'Captain',
			150 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Prism Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Prism Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Prism Tanks';
			$a->heap_name = 'UNB';
			$a->object_name = 'SREF';
			$a->cameo = 'sreficon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Tank Destroyers achievements
		$map = [
			3 => 'Noob',
			10 => 'Recruit',
			30 => 'Captain',
			50 => 'Veteran',
			100 => 'Master',
			300 => 'Legend',
			500 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Tank Destroyers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Tank Destroyers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Tank Destroyers';
			$a->heap_name = 'UNB';
			$a->object_name = 'TNKD';
			$a->cameo = 'tnkdicon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Battle Fortresses achievements
			$map = [
				10 => 'Noob',
				30 => 'Recruit',
				60 => 'Captain',
				125 => 'Veteran',
				300 => 'Master',
				500 => 'Legend',
				1000 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Build Battle Fortresses';
				$a->achievement_type = $type;
				$a->achievement_name = 'Battle Fortresses ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Battle Fortresses';
				$a->heap_name = 'UNB';
				$a->object_name = 'BFRT';
				$a->cameo = 'bfrticon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Harriers achievements
		$map = [
			5 => 'Noob',
			20 => 'Recruit',
			45 => 'Captain',
			80 => 'Veteran',
			150 => 'Master',
			300 => 'Legend',
			500 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Harriers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Harriers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Harriers';
			$a->heap_name = 'UNB';
			$a->object_name = 'ORCA';
			$a->cameo = 'orcaicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Black Eagles achievements
		$map = [
			5 => 'Noob',
			20 => 'Recruit',
			45 => 'Captain',
			80 => 'Veteran',
			150 => 'Legend',
			300 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Black Eagles';
			$a->achievement_type = $type;
			$a->achievement_name = 'Black Eagles ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Black Eagles';
			$a->heap_name = 'UNB';
			$a->object_name = 'BEAG';
			$a->cameo = 'beagicon';
			$a->unlock_count = $key;
			$a->save();
		}

		return $order;
	}

	/**
	 * INTERMEDIATE Achievements for building allied units
	 */
	private function alliedIntermediateBuild($ladderId, $order)
	{
		$type = 'INTERMEDIATE';

		//Build G.I. achievements
		$map = [
			20 => 'Noob',
			50 => 'Veteran',
			100 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build GIs';
			$a->achievement_type = $type;
			$a->achievement_name = 'GI ' . $val;
			$a->achievement_description = 'Build ' . $key . ' GIs in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'E1';
			$a->cameo = 'e1icon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Allied Dogs achievements
		$map = [
			20 => 'Noob',
			50 => 'Veteran',
			100 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Allied Attack Dogs';
			$a->achievement_type = $type;
			$a->achievement_name = 'Attack Dog ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Attack Dogs in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'ADOG';
			$a->cameo = 'adogicon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Guardian GIs achievements
			$map = [
				15 => 'Noob',
				30 => 'Veteran',
				60 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Build Guardian GIs';
				$a->achievement_type = $type;
				$a->achievement_name = 'Guardian GIs ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Guardian GIs in one game';
				$a->heap_name = 'UNB';
				$a->object_name = 'GGI';
				$a->cameo = 'gdgiicon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Snipers achievements
		$map = [
			10 => 'Noob',
			25 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Snipers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Snipers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Snipers in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'SNIPE';
			$a->cameo = 'snipicon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Navy Seal achievements
			$map = [
				5 => 'Noob',
				15 => 'Veteran',
				30 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Build Navy Seals';
				$a->achievement_type = $type;
				$a->achievement_name = 'Navy Seals ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Navy Seals in one game';
				$a->heap_name = 'UNB';
				$a->object_name = 'GHOST';
				$a->cameo = 'gosticon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Rocketeers achievements
		$map = [
			15 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Rocketeers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Rocketeers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Rocketeers in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'JUMPJET';
			$a->cameo = 'jjeticon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Chrono Legionnaires achievements
		$map = [
			5 => 'Noob',
			15 => 'Veteran',
			30 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Chrono Legionnaires';
			$a->achievement_type = $type;
			$a->achievement_name = 'Chrono Legionnaires ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Chrono Legionnaires in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'CLEG';
			$a->cameo = 'clegicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Grizzly Tanks achievements
		$map = [
			15 => 'Noob',
			40 => 'Veteran',
			75 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Grizzly Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Grizzly ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Grizzly Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'MTNK';
			$a->cameo = 'gtnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build IFVs achievements
		$map = [
			15 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build IFVs';
			$a->achievement_type = $type;
			$a->achievement_name = 'IFVs ' . $val;
			$a->achievement_description = 'Build ' . $key . ' IFVs in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'FV';
			$a->cameo = 'fvicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Mirage Tanks achievements
		$map = [
			10 => 'Noob',
			25 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Mirage Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Mirage Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Mirage Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'MGTK';
			$a->cameo = 'rtnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Prism Tanks achievements
		$map = [
			10 => 'Noob',
			25 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Prism Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Prism Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Prism Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'SREF';
			$a->cameo = 'sreficon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Tank Destroyers achievements
		$map = [
			10 => 'Noob',
			25 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Tank Destroyers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Tank Destroyers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Tank Destroyers in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'TNKD';
			$a->cameo = 'tnkdicon';
			$a->unlock_count = $key;
			$a->save();
		}

		if ($ladderId !== 5) //don't create this achievement for RA2 ladder
		{
			//Build Battle Fortresses achievements
			$map = [
				5 => 'Noob',
				15 => 'Veteran',
				30 => 'Elite'
			];
			foreach ($map as $key => $val)
			{
				$a = new \App\Achievement();
				$a->ladder_id = $ladderId;
				$a->order = $order++;
				$a->tag = 'Build Battle Fortresses';
				$a->achievement_type = $type;
				$a->achievement_name = 'Battle Fortresses ' . $val;
				$a->achievement_description = 'Build ' . $key . ' Battle Fortresses in one game';
				$a->heap_name = 'UNB';
				$a->object_name = 'BFRT';
				$a->cameo = 'bfrticon';
				$a->unlock_count = $key;
				$a->save();
			}
		}

		//Build Harriers achievements
		$map = [
			5 => 'Noob',
			12 => 'Veteran',
			24 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Harriers';
			$a->achievement_type = $type;
			$a->achievement_name = 'Harriers ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Harriers in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'ORCA';
			$a->cameo = 'orcaicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Black Eagles achievements
		$map = [
			5 => 'Noob',
			12 => 'Veteran',
			24 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Black Eagles';
			$a->achievement_type = $type;
			$a->achievement_name = 'Black Eagles ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Black Eagles in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'BEAG';
			$a->cameo = 'beagicon';
			$a->unlock_count = $key;
			$a->save();
		}

		return $order;
	}

	/**
	 * Career Achievements for building Yuri units
	 */
	private function yuriCareerBuild($ladderId, $order)
	{
		$type = 'CAREER';

		//Build Initiates achievements
		$map = [
			25 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			300 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Initiates';
			$a->achievement_type = $type;
			$a->achievement_name = 'Initiate ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Initiates';
			$a->heap_name = 'UNB';
			$a->object_name = 'INIT';
			$a->cameo = 'initicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Virus achievements
		$map = [
			10 => 'Noob',
			50 => 'Recruit',
			125 => 'Captain',
			300 => 'Veteran',
			500 => 'Master',
			700 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Viruses';
			$a->achievement_type = $type;
			$a->achievement_name = 'Virus ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Viruses';
			$a->heap_name = 'UNB';
			$a->object_name = 'VIRUS';
			$a->cameo = 'vrusicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Yuri Clone achievements
		$map = [
			10 => 'Noob',
			30 => 'Recruit',
			60 => 'Captain',
			125 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Yuri Clones';
			$a->achievement_type = $type;
			$a->achievement_name = 'Yuri Clone ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Yuri Clones';
			$a->heap_name = 'UNB';
			$a->object_name = 'YURI';
			$a->cameo = 'clonicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Brute achievements
		$map = [
			25 => 'Noob',
			75 => 'Recruit',
			150 => 'Captain',
			300 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Brutes';
			$a->achievement_type = $type;
			$a->achievement_name = 'Brutes ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Brutes';
			$a->heap_name = 'UNB';
			$a->object_name = 'BRUTE';
			$a->cameo = 'bruticon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Yuri Prime achievements
		$map = [
			5 => 'Noob',
			15 => 'Recruit',
			30 => 'Captain',
			60 => 'Veteran',
			100 => 'Master',
			300 => 'Legend',
			500 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Yuri Primes';
			$a->achievement_type = $type;
			$a->achievement_name = 'Yuri Prime ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Yuri Primes';
			$a->heap_name = 'UNB';
			$a->object_name = 'YURIPR';
			$a->cameo = 'yurpicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Lasher Tanks achievements
		$map = [
			30 => 'Noob',
			100 => 'Recruit',
			300 => 'Captain',
			500 => 'Veteran',
			1000 => 'Master',
			2000 => 'Legend',
			3000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Lasher Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Lasher ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Lasher Tanks';
			$a->heap_name = 'UNB';
			$a->object_name = 'LTNK';
			$a->cameo = 'ltnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build gattling tanks achievements
		$map = [
			20 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			300 => 'Veteran',
			750 => 'Master',
			1250 => 'Legend',
			2500 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Gattling Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Gattling Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Gattling Tanks';
			$a->heap_name = 'UNB';
			$a->object_name = 'YTNK';
			$a->cameo = 'ytnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Magnetrons achievements
		$map = [
			20 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			200 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Magnetrons';
			$a->achievement_type = $type;
			$a->achievement_name = 'Magnetrons ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Magnetrons';
			$a->heap_name = 'UNB';
			$a->object_name = 'TELE';
			$a->cameo = 'teleicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Masterminds achievements
		$map = [
			10 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			250 => 'Veteran',
			500 => 'Master',
			1000 => 'Legend',
			2000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Masterminds';
			$a->achievement_type = $type;
			$a->achievement_name = 'Masterminds ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Masterminds';
			$a->heap_name = 'UNB';
			$a->object_name = 'MIND';
			$a->cameo = 'mindicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build DISK achievements
		$map = [
			10 => 'Noob',
			50 => 'Recruit',
			100 => 'Captain',
			150 => 'Veteran',
			300 => 'Master',
			500 => 'Legend',
			1000 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Floating Disks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Floating Disk ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Floating Disk';
			$a->heap_name = 'UNB';
			$a->object_name = 'DISK';
			$a->cameo = 'diskicon';
			$a->unlock_count = $key;
			$a->save();
		}

		return $order;
	}

	/**
	 * Intermediate Achievements for building Yuri units
	 */
	private function yuriIntermediateBuild($ladderId, $order)
	{
		$type = 'Intermediate';

		//Build Initiates achievements
		$map = [
			15 => 'Noob',
			40 => 'Veteran',
			75 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Initiates';
			$a->achievement_type = $type;
			$a->achievement_name = 'Initiate ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Initiates in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'INIT';
			$a->cameo = 'initicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Virus achievements
		$map = [
			5 => 'Noob',
			20 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Viruses';
			$a->achievement_type = $type;
			$a->achievement_name = 'Virus ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Viruses in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'VIRUS';
			$a->cameo = 'vrusicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Yuri Clone achievements
		$map = [
			5 => 'Noob',
			20 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Yuri Clones';
			$a->achievement_type = $type;
			$a->achievement_name = 'Yuri Clone ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Yuri Clones in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'YURI';
			$a->cameo = 'clonicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Brute achievements
		$map = [
			15 => 'Noob',
			100 => 'Veteran',
			300 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Brutes';
			$a->achievement_type = $type;
			$a->achievement_name = 'Brutes ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Brutes in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'BRUTE';
			$a->cameo = 'bruticon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Lasher Tanks achievements
		$map = [
			10 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Lasher Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Lasher ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Lasher Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'LTNK';
			$a->cameo = 'ltnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build gattling tanks achievements
		$map = [
			10 => 'Noob',
			30 => 'Veteran',
			60 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Gattling Tanks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Gattling Tanks ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Gattling Tanks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'YTNK';
			$a->cameo = 'ytnkicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Magnetrons achievements
		$map = [
			8 => 'Noob',
			20 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Magnetrons';
			$a->achievement_type = $type;
			$a->achievement_name = 'Magnetrons ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Magnetrons in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'TELE';
			$a->cameo = 'teleicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build Masterminds achievements
		$map = [
			10 => 'Noob',
			30 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Masterminds';
			$a->achievement_type = $type;
			$a->achievement_name = 'Masterminds ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Masterminds in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'MIND';
			$a->cameo = 'mindicon';
			$a->unlock_count = $key;
			$a->save();
		}

		//Build DISK achievements
		$map = [
			10 => 'Noob',
			25 => 'Veteran',
			50 => 'Elite'
		];
		foreach ($map as $key => $val)
		{
			$a = new \App\Achievement();
			$a->ladder_id = $ladderId;
			$a->order = $order++;
			$a->tag = 'Build Floating Disks';
			$a->achievement_type = $type;
			$a->achievement_name = 'Floating Disk ' . $val;
			$a->achievement_description = 'Build ' . $key . ' Floating Disks in one game';
			$a->heap_name = 'UNB';
			$a->object_name = 'DISK';
			$a->cameo = 'diskicon';
			$a->unlock_count = $key;
			$a->save();
		}

		return $order;
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('achievements');
	}
}
