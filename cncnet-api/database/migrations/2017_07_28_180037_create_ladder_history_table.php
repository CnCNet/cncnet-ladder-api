<?php

use App\Models\LadderHistory;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLadderHistoryTable extends Migration 
{
	public function up()
	{
		Schema::create('ladder_history', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('ladder_id');
            $table->dateTime('starts');
            $table->dateTime('ends');
            $table->string('short');
			$table->softDeletes();
		});

        $this->seed();
	}

    private function seed()
    {
        $ladders = \App\Models\Ladder::all();
        foreach($ladders as $ladder)
        {
            for($times = 0; $times < 5; $times++)
            {
                $year = 2017 + $times;
                for($month = 0; $month <= 12; $month++)
                {
                    $date = Carbon::create($year, 01, 01, 0)->addMonth($month); 
                    $start = $date->startOfMonth()->toDateTimeString();
                    $ends = $date->endOfMonth()->toDateTimeString();

                    $ladderHistory = LadderHistory::where("starts", "=", $start)
                        ->where("ends", "=", $ends)
                        ->where("ladder_id", "=", $ladder->id)
                        ->first();

                    if ($ladderHistory == null)
                    {
                        $ladderHistory = new LadderHistory();
                        $ladderHistory->ladder_id = $ladder->id;
                        $ladderHistory->starts = $start;
                        $ladderHistory->ends = $ends;
                        $ladderHistory->short = $date->month . "-" . $date->year;
                        $ladderHistory->save();
                    }
                }
            }
        }
    }

    public function down()
    {
    	Schema::drop('ladder_history');
    }
}
