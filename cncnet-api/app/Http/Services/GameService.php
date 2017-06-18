<?php namespace App\Http\Services;

class GameService
{
    private $maxPlayers;
    private $playerService;

    public function __construct()
    {
        $this->maxPlayers = 8;
        $this->playerService = new PlayerService();
    }

    public function getUniqueGameIdentifier($result)
    {
        foreach ($result as $k => $v)
        {
            if($k == "IDNO")
                return $v["value"];
        }
        return null;
    }

    // TODO - Need to verify game stats against other players if game exists.
    public function saveGameStats($result, $gameId, $senderId)
    {
        // TODO - check we don't have GameStats for GameID and PlayerId already
        $gameStats = \App\GameStats::where("player_id", "=", $senderId)
            ->where("game_id", "=", $gameId)->first();

        if ($gameStats != null)
            return 200;

        $stats = new \App\GameStats();
        $stats->player_id = $senderId;
        $stats->save();

        $playerStats = new \App\PlayerStats();
        $playerStats->player_id = $senderId;
        $playerStats->game_stats_id = $stats->id;
        $playerStats->player_stats = json_encode($result);
        $playerStats->save();

        // Loop our submitted game result
        $playerIndex = 0;
        while ($playerIndex <= $this->maxPlayers)
        {
            foreach($result as $k => $v)
            {
                if (isset($result["NAM" . $playerIndex]))
                {
                    // Player Index from Stats File, e.g nam#(index)
                    $gamePlayerIndex = substr($k, -1);

                    $player = $this->playerService->findPlayerByName($result["NAM" . $playerIndex]["value"]);
                    if ($player == null)
                        break;

                    // Store Stats Info on Player
                    if ($gamePlayerIndex == $playerIndex)
                    {
                        $key = substr($k, 0, -1);

                        // Add Player to the game if we haven't already
                        $pg = \App\PlayerGame::where("game_id", "=", $gameId)
                            ->where("player_id", "=", $player->id)->first();

                        // TODO - Add proper logic for determining game result
                        if($pg == null)
                        {
                            $won = false;
                            if ($key == "CMP" && $v["value"] == 256)
                            {
                                $this->playerService->createPlayerGame($player, $gameId, true);
                            }
                            else if ($key == "CMP" && $v["value"] != 256)
                            {
                                $this->playerService->createPlayerGame($player, $gameId, false);
                            }
                        }

                        if (in_array(strtolower($key), $stats->playerStatsColumns)) 
                        {
                            $stats->{strtolower($key)} = $v["value"];
                        }
                    }
                }
                else if (in_array(strtolower($k), $stats->gameStatsColumns)) 
                {
                    // Store Non Player Specific Stats
                    $stats->{strtolower($k)} = $v["value"];
                }
            }

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
 
        $pad = 0;
        $result = [];

        while (!feof($fh)) 
        {
            $data = fread($fh, 8);
            if (!$data) 
            {
                break;
            }
 
            $ttl = unpack("A4tag/ntype/nlength", $data);
            $pad = ($ttl["length"] % 4) ? 4 - ($ttl["length"] %  4) : 0;
            
            if ($ttl["length"] > 0) 
            {
                $data = fread($fh, $ttl["length"]);
 
                if ($pad > 0 ) 
                {
                    fread($fh, $pad);
                }

                $fieldValueArr = $this->getFieldValue($ttl, $data);
                $result[$ttl["tag"]] = ["tag" => $ttl["tag"], "length" => $ttl["length"], "raw" => json_encode($fieldValueArr["raw"]), "value" => $fieldValueArr["val"]];
            }
        }

        $types = array ("CRA","BLC","BLK","PLK","UNK","INK","BLL","PLL","UNL","INL","BLB","PLB","UNB","INB");
        $gameUnitTypes = config("types.YR");

        foreach ($types as $tag) 
        {
            $lookup = $gameUnitTypes[substr($tag, 0, 2)];

            for ($i = 0; $i < 8; $i++) 
            {
                if (isset($result["$tag$i"])) 
                {
                    $raw = json_decode($result["$tag$i"]["raw"]);
                    $length = $result["$tag$i"]["length"];
                    
                    for ($j = 0, $t = 0; $j < $length; $j += 4, $t++) 
                    {
                        $count = unpack("N", substr($raw, $j, 4))[1];
                        if ($count >= 0) 
                        {
                            if ($lookup && $lookup[$t]) 
                            {
                                $result["$tag$i"]["counts"][$lookup[$t]] = $count;
                            }
                            else 
                            {
                                $result["$tag$i"]["counts"][$t] = $count;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function getFieldValue($ttl, $data)
    {
        $response = ["raw" => null, "val" => null];

        switch ($ttl["type"]) 
        {
            //FIELDTYPE_BYTE
            case 1:
                $v = unpack("C", $data);
                $response["val"] = $v[1];
                break;
 
            //FIELDTYPE_BOOLEAN
            case 2:
                $v = unpack("C", $data);
                if ($v[1] == 0) 
                {
                    $response["val"] = false;
                    break;
                }
                else 
                {
                    $response["val"] = true;
                    break;
                }
 
            //FIELDTYPE_SHORT
            case 3:
                $v = unpack("n", $data);
                $response["val"] = $v[1];
                break;
 
            //FIELDTYPE_UNSIGNED_SHORT
            case 4:
                $v = unpack("n", $data);
                $response["val"] = $v[1];
                break;
 
            //FIELDTYPE_LONG
            case 5:
                $v = unpack("N", $data);
                $response["val"] = $v[1];
                break;
 
            //FIELDTYPE_UNSIGNED_LONG
            case 6:
                $v = unpack("N", $data);
                $response["val"] = $v[1];
                break;
 
            //FIELDTYPE_CHAR
            case 7:
                $ttl["length"] -= 1;
                $v = unpack("a$ttl[length]", $data);
                $response["val"] = $v[1];
                break;
 
            //FIELDTYPE_CUSTOM_LENGTH
            case 20:
                $response["val"] = null;
                $response["raw"] = substr($data, 0, $ttl["length"]);;
                break;
        }

        return $response;
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