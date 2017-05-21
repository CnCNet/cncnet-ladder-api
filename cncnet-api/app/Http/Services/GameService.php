<?php namespace App\Http\Services;

class GameService
{
    private $maxPlayers;

    public function __construct()
    {
        $this->maxPlayers = 8;
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

        // Max no. of real players in a game
        $playerIndex = 0;

        while ($playerIndex <= $this->maxPlayers)
        {
            // Loop our submitted game result
            foreach($result as $k => $v)
            {
                // Get the stats Player ID matched to Player Username
                if (isset($result["nam" . $playerIndex]) && $player->username == $result["nam" . $playerIndex])
                {
                    // Player Index from Stats File, e.g nam#(index)
                    $gamePlayerIndex = substr($k, -1);

                    // Store Stats Info on Player
                    if ($gamePlayerIndex == $playerIndex)
                    {
                        $key = substr($k, 0, -1);

                        if (in_array($key, $stats->playerStatsColumns)) 
                        {
                            $stats->{$key} = $v;
                        }
                    }
                }
                else if (in_array($k, $stats->gameStatsColumns)) 
                {
                    // Store Non Player Specific Stats
                    $stats->{$k} = $v;
                }
            }

            $stats->player_id = $player->id;
            $stats->game_id = $gameId;
            $stats->save();

            $playerIndex++;
        }

        return 200;
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
        if($file == null)
            return null;

        $fh = fopen($file, "r");
        $data = fread($fh, 4);
 
        if (!$data) {
           return "Error";
        }
 
        $stats_ver = unpack("V", $data)[1];
 
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
        $ladderGame->plrs = $gameStats->plrs;

        $ladderGame->save();
    }

    public function findOrCreateGame($id, $ladder)
    {
        $game = \App\Game::where("wol_game_id", "=", $id)->first();

        if ($game == null)
        {
            $game = new \App\Game();
            $game->ladder_id = $ladder->id;
            $game->wol_game_id = $id;
            $game->save();
        }

        return $game;
    }
}