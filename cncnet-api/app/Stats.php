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

    public function country($side)
    {     
        $val = json_decode($side);
        if ($val == null) return "";

        switch($val->value)
        {
            case 0:
                return "um";
            case 1:
                return "kr";
            case 2:
                return "fr";
            case 3:
                return "de";
            case 4:
                return "gb";
            case 5:
                return "ly";
            case 6:
                return "iq";
            case 7:
                return "cu";
            case 8: 
                return "ru";
            case 9:
                return "yuri";
        }
        return "";
    }

    public function colour($colour)
    {     
        $val = json_decode($colour);
        if ($val == null) return "";

        switch($val->value)
        {
            case 3:
                return "yellow";
            case 13:
                return "orange";
            case 11:
                return "red";
            case 15: 
                return "pink";
            case 17:
                return "purple";
            case 21:
                return "blue";
            case 25:
                return "teal";
            case 29:
                return "green";
        }

        return "random";
    }
}