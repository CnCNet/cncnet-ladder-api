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

    // TODO - Need to verify game stats against other players if game exists.
    public function saveGameStats($result, $gameId, $player)
    {
        if ($gameId == null || $player == null || $result == null)
            return "Missing game information";;

        $gameStats = \App\GameStats::where("player_id", "=", $player->id)
            ->where("game_id", "=", $gameId)->first();

        if ($gameStats != null)
            return "Game stats already exist for this player and this game";

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
            return "Could not match stats player name to user database";

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
        return 200;
    }
    
    public function saveRawStats($result, $gameId, $ladderId, $sha1)
    {
        $raw = new \App\GameRaw();
        $raw->packet = json_encode($result);
        $raw->game_id = $gameId;
        $raw->ladder_id = $ladderId;
        $raw->hash = $sha1;
        $raw->save();

        return $raw;
    }

    // Credit: https://github.com/dkeetonx
    public function processStatsDmp($file)
    {
        if($file == null) return;

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
                $result[strtolower($ttl["tag"])] = $val;
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

    public function saveGameDetails($ladderGame, $gameStats)
    {
        // TODO refine

        $ladderGame->afps = $gameStats->afps;
        $ladderGame->oosy = $gameStats->oosy;
        $ladderGame->bamr = $gameStats->bamr;
        $ladderGame->crat = $gameStats->crat;
        $ladderGame->dura = $gameStats->dura;
        $ladderGame->cred = $gameStats->cred;
        $ladderGame->shrt = $gameStats->shrt;
        $ladderGame->supr = $gameStats->supr;
        $ladderGame->unit = $gameStats->unit;
        $ladderGame->plrs = $gameStats->unit;

        $ladderGame->save();
    }
}