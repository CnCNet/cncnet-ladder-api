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

    public function playerGameReport()
    {
        return $this->belogsTo('App\PlayerGameReport');
    }

    public function faction($game)
    {
        $ladder = \App\Ladder::where("abbreviation", "=", $game)->first();

        $local_id = null;
        if ($game == 'yr')
        {
            $local_id = json_decode($this->cty)->value;
        }
        else
        {
            $local_id = json_decode($this->sid)->value;
        }

        if ($local_id === null) return "";

        if (!is_numeric($local_id))
        {
            // RA uses strings as side id's
            return strtolower($local_id);
        }

        $side = $ladder->sides()->where('local_id', $local_id)->first();

        return $side !== null ? strtolower($side->name) : "";
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