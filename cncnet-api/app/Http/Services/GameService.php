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

    public function saveGameStats($result, $gameId, $playerId, $ladderId, $cncnetGame)
    {
        $game = \App\Game::where("id", "=", $gameId)->first();
        $gameStats = \App\GameStats::where("player_id", "=", $playerId)
            ->where("game_id", "=", $gameId)
            ->first();

        if ($gameStats != null)
        {
            return 600;
        }

        $stats = new \App\Stats();
        $stats->save();

        $gameStats = new \App\GameStats();
        $gameStats->player_id = $playerId;
        $gameStats->stats_id = $stats->id;
        $gameStats->game_id = $gameId;
        $gameStats->save();

        $player = \App\Player::where("id", "=", $playerId)->first();

        if ($player == null)
        {
            return 601;
        }

        $id = -1; // Player Index
        foreach($result as $key => $value)
        {
            $property = substr($key, 0, -1);

            if ($property == "NAM")
            {
                if (isset($value["value"]) && $value["value"] == $player->username)
                {
                    $id = substr($key, -1);
                }
            }
        }

        if ($id != -1)
        {
            $playerDead = 0;
            $opponent = '';

            foreach($result as $key => $value)
            {
                $cid = substr($key, -1); // Current Index
                $property = substr($key, 0, -1); // Property without index

                if ($cid == $id)
                {
                    // Save Game Specific Stats like buildings bought, destroyed etc
                    if (in_array(strtolower($property), $stats->gameStatsColumns))
                    {
                        $stats->{strtolower($property)} = json_encode($value);
                    }

                    $playerGame = \App\PlayerGame::where("game_id", "=", $gameId)
                        ->where("player_id", "=", $player->id)
                        ->first();

                    // Works for now
                    if ($cncnetGame == "ra")
                    {
                        if ($id == 1)
                        {
                            $opponent = $result["NAM2"]["value"];
                        }
                        else if ($id == 2)
                        {
                            $opponent = $result["NAM1"]["value"];
                        }
                    }
                    else
                    {
                        if ($id == 0)
                        {
                            $opponent = $result["NAM1"]["value"];
                        }
                        else if ($id == 1)
                        {
                            $opponent = $result["NAM0"]["value"];
                        }
                    }

                    $opponent = \App\Player::where("username", "=", $opponent)
                        ->where("ladder_id", "=", $ladderId)->first();

                    if ($opponent == null)
                    {
                        $game->delete();
                        $stats->delete();
                        $gameStats->delete();

                        return 602;
                    }

                    if ($playerGame == null && $property == "CMP")
                    {
                        $gameResult = $value["value"];
                        switch($gameResult)
                        {
                            case $gameResult & GameResult::COMPLETION_WON:
                                $this->playerService->createPlayerGame($player, $opponent, $gameId, true);
                                break;
                            case $gameResult & GameResult::COMPLETION_DISCONNECTED:
                            case $gameResult & GameResult::COMPLETION_NO_COMPLETION:
                            case $gameResult & GameResult::COMPLETION_QUIT:
                            case $gameResult & GameResult::COMPLETION_DEFEATED:
                            default:
                                $this->playerService->createPlayerGame($player, $opponent, $gameId, false);
                        }
                    }
                    else if ($playerGame == null && $cncnetGame == "ra" && ($property == "DED" || $property == "RSG")) // Just extra safety
                    {
                        $isDead = $value["value"];
                        if ($isDead == 1)
                        {
                            $playerDead = 1;
                        }
                    }
                }
            }

            if ($cncnetGame == "ra")
            {
                /*
                if ($result["CMPL"]["value"] == 64) //draw
                {
                    
                }
                else if ($result["sdfx"]["value"] == 1) //disconnect
                {
                    
                }
                else if ($result["oosy"]["value"] == 1) //out of sync
                {
                    
                }
                else if ($result["dura"]["value"] < 60) //game too short
                {
                    
                }
                else 
                */
                if ($playerDead == 1)
                {
                    $this->playerService->createPlayerGame($player, $opponent, $gameId, false);
                }
                else if ($playerDead == 0)
                {
                    $this->playerService->createPlayerGame($player, $opponent, $gameId, true);
                }
            }

        }

        $gameStats->save();
        $stats->save();
        $game->save();

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
    public function processStatsDmp($file, $cncnetGame)
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
                $result[$ttl["tag"]] = ["tag" => $ttl["tag"], "length" => $ttl["length"], "raw" => base64_encode($fieldValueArr["raw"]), "value" => $fieldValueArr["val"]];
            }
        }

        $types = array ("CRA","BLC","BLK","PLK","UNK","INK","BLL","PLL","UNL","INL","BLB","PLB","UNB","INB");
        $gameUnitTypes = config("types." . strtoupper($cncnetGame));

        foreach ($types as $tag)
        {
            $lookup = $gameUnitTypes[substr($tag, 0, 2)];

            for ($i = 0; $i < 8; $i++)
            {
                if (isset($result["$tag$i"]))
                {
                    $raw = base64_decode($result["$tag$i"]["raw"]);
                    $length = $result["$tag$i"]["length"];

                    for ($j = 0, $t = 0; $j < $length; $j += 4, $t++)
                    {
                        $count = unpack("N", substr($raw, $j, 4))[1];
                        if ($count >= 0)
                        {
                            if ($lookup && count($lookup) > $t && $lookup[$t])
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

    public function findOrCreateGame($result, $ladder)
    {
        $id = $this->getUniqueGameIdentifier($result);

        $game = \App\Game::where("wol_game_id", "=", $id)->first();

        if ($game == null)
        {
            $game = new \App\Game();
            $game->ladder_history_id = $ladder->id;
            $game->wol_game_id = $id;
            $game->save();
        }

        $ladderGame = \App\LadderGame::where("game_id", "=", $game->id)->first();
        if ($ladderGame == null)
        {
            $ladderGame = new \App\LadderGame();
            $ladderGame->game_id = $game->id;
            $ladderGame->ladder_history_id = $ladder->id;
            $ladderGame->save();
        }

        foreach($result as $key => $value)
        {
            $gameProperty = substr($key, -1);

            if(!is_numeric($gameProperty))
            {
                // Save Game Details like average fps, out of sync errors etc
                if (in_array(strtolower($key), $game->gameColumns))
                {
                    $game->{strtolower($key)} = $value["value"];
                }
            }
        }

        $game->save();
        return $game;
    }

    private function getUniqueGameIdentifier($result)
    {
        foreach ($result as $k => $v)
        {
            if($k == "IDNO")
                return $v["value"];
        }
        return null;
    }
}