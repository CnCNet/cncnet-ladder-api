<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{

    protected $table = 'achievements';

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function test()
    {
        $lmap = [
            1 => 'Yuri\'s Revenge',
            2 => 'Tiberian Sun',
            3 => 'Red Alert',
            5 => 'Red Alert 2',
            7 => 'SFJ',
            8 => 'Blitz'
        ];
        foreach ($lmap as $ladderId => $ladderName)
        {
            echo 'key : '  . $ladderId . PHP_EOL;
            echo 'value: ' . $ladderName . explode(" ", $ladderName)[0] . PHP_EOL;
            echo PHP_EOL;
        }
    }
}
