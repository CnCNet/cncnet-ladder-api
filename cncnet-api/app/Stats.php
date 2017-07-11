<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Stats extends Model
{
	protected $table = 'stats';

	protected $fillable = [
        'sid', 'col', 'cty',
        'crd',  'crd', 'unl', 'inl', 'pll',
        'bll', 'unb', 'inb', 'plb', 'blb', 'unk', 'ink',
        'plk', 'blk', 'blc', 'cra', 'hrv'
    ];

	public $gameStatsColumns = [
        'sid', 'col', 'cty',
        'crd',  'crd', 'unl', 'inl', 'pll',
        'bll', 'unb', 'inb', 'plb', 'blb', 'unk', 'ink',
        'plk', 'blk', 'blc', 'cra', 'hrv'
    ];
    
    public $timestamps = false;

    public function faction($game, $val)
    {
        $ladder = \App\Ladder::where("abbreviation", "=", $game)->first();
        $val = json_decode($val);
        if ($val == null) return "";

        switch($val->value)
        {
            case 0:
                return "america";
            case 1:
                return "korea";
            case 2:
                return "france";
            case 3:
                return "germany";
            case 4:
                return "britain";
            case 5:
                return "libya";
            case 6:
                return "iraq";
            case 7:
                return "cuba";
            case 8: 
                return "russia";
            case 9:
                return "yuri";
            case 10:
                return "gdi";
            case 11:
                return "nod";
            case 12: 
                return "neutral";
            case 13: 
                return "special";
            case 14:
            default: 
                return "";
        }


        return -1;
    }
}