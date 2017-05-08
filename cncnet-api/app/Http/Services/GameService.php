<?php namespace App\Http\Services;

class GameService
{
    public function __construct()
    {

    }

    public function getUniqueGameIdentifier($result)
    {
        foreach ($result as $k => $v)
        {
            if($k == "idno")
                return $v;
        }
        return null;
    }

    public function saveGameStats($result, $gameId, $player)
    {
        if ($gameId == null || $player == null || $result == null)
            return null;

        $gameStats = \App\GameStats::where("player_id", "=", $player->id)
            ->where("game_id", "=", $gameId)->first();

        if ($gameStats != null)
            return null;

        // Safe to record game stats
        $stats = new \App\GameStats();
        $stats->save();

        $playerName = null;

        // Safety - is the player who they say
        $playerIndex = -1;

        foreach($result as $k => $v)
        {
            $index = substr($k, strlen($k) - 1);
            
            if (is_numeric($index))
            {
                $playerIndex = $index;
            }

            if ($playerIndex != -1)
            {
                if($result["nam" . $playerIndex] != null)
                {
                    $playerName = $result["nam" . $playerIndex];
                    
                    if($player->username == $playerName)
                    {
                        $playerName = $player->username;
                        break;
                    }
                } 
            } 
        }

        if($playerName == null)
            return null;

        foreach($result as $k => $v)
        {
            $stats->game_id = $gameId;
            $stats->player_id = $player->id;

            $index = substr($k, strlen($k) - 1);
            $newKey = substr($k, 0, -1);
            
            // TODO needs some thought
            if (is_numeric($index) && $index == $playerIndex)
            {
                if (in_array($newKey, $stats->columns)) 
                {
                    $stats->{$newKey} = $v;
                }
            }
            else
            {
                if (in_array($newKey, $stats->columns)) 
                {
                    $stats->{$newKey} = $v;
                }

                if (in_array($k, $stats->columns)) 
                {
                    echo "Saving " . $stats->{$k} . $v;
                    $stats->{$k} = $v;
                }
            }
        }

        $stats->save();
        return $stats;
    }

    private function setStatValue()
    {
        
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
            
            //print "$ttl[tag] $ttl[type] $ttl[length]";
 
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
                    $result[strtolower($ttl["tag"])] = $val;
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