<?php namespace App\Http\Services;

class GameService
{
    public function __construct()
    {

    }

    public function saveGameStats($result, $gameId, $playerId)
    {
        if ($gameId == null || $playerId = null || $result == null)
            return "Invalid request for saving game stats";

        $gameStats = \App\GameStats::where("player_id", "=", $playerId)
            ->where("game_id", "=", $gameId)->first();

        if ($gameStats != null)
            return "Stats already received for this game";

        // Safe to record game stats
        $stats = new \App\GameStats();
        foreach($result as $k => $v)
        {
            $stats->game_id = $gameId;
            $stats->player_id = $playerId;
            if(isset($stats->{$k}))
            {
                $stats->{$k} = $v;
            }
        }
        $stats->save();

        return $stats;
    }

    public function saveRawStats($result, $gameId, $ladderId)
    {
        $raw = new \App\GameRaw();
        $raw->packet = json_encode($result);
        $raw->game_id = $gameId;
        $raw->ladder_id = $ladderId;
        $raw->save();

        return $raw;
    }

    // Credit: https://github.com/dkeetonx
    public function processStatsDmp($file)
    {
        $fh = fopen($file, "r");
        $data = fread($fh, 4);
 
        if (!$data) {
           return "Error";
        }
 
        $stats_ver = unpack("V", $data)[1];
 
        print "Stats version = $stats_ver\n";
 
        $pad = 0;
        $result = [];

        while (!feof($fh)) 
        {
            $data = fread($fh, 8);
            if (!$data) 
            {
                // exit loop here
                break;
            }
 
            $ttl = unpack("A4tag/ntype/nlength", $data);
            $pad = ($ttl["length"] % 4) ? 4 - ($ttl["length"] %  4) : 0;
            
            print "$ttl[tag] $ttl[type] $ttl[length]";
 
            if ($ttl["length"] > 0) 
            {
                $data = fread($fh, $ttl["length"]);
 
                if ($pad > 0 ) 
                {
                    fread($fh, $pad);
                }

                $val = $this->getFieldValue($ttl, $data);
                
                if($val != null)
                {
                    $result[$ttl["tag"]] = $val;
                }
            }
        }
        return $result;
    }

    private function getFieldValue($ttl, $data)
    {
        switch ($ttl["type"]) 
        {
            //FIELDTYPE_BYTE
            case 1:
                $v = unpack("C", $data);
                return $v[1];
 
            //FIELDTYPE_BOOLEAN
            case 2:
                $v = unpack("C", $data);
                if ($v[1] == 0) 
                {
                    return false;
                }
                else 
                {
                    return true;
                }
 
            //FIELDTYPE_SHORT
            case 3:
                $v = unpack("n", $data);
                return $v[1];
 
            //FIELDTYPE_UNSIGNED_SHORT
            case 4:
                $v = unpack("n", $data);
                return $v[1];
 
            //FIELDTYPE_LONG
            case 5:
                $v = unpack("N", $data);
                return $v[1];
 
            //FIELDTYPE_UNSIGNED_LONG
            case 6:
                $v = unpack("N", $data);
                return $v[1];
 
            //FIELDTYPE_CHAR
            case 7:
                $ttl["length"] -= 1;
                $v = unpack("a$ttl[length]", $data);
                return $v[1];
 
            //FIELDTYPE_CUSTOM_LENGTH
            case 20:
                $v = unpack("C$ttl[length]", $data);
                return "<<raw data HERE>>";
        }

        return null;
    }
}