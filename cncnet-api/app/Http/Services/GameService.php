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


        $player = \App\Player::where("id", "=", $playerId)->first();

        if ($player == null)
        {
            return null;
        }

        $gameReport = new \App\GameReport;
        $gameReport->game_id = $game->id;
        $gameReport->player_id = $playerId;
        $gameReport->best_report = false;
        $gameReport->manual_report = false;
        $gameReport->duration = 0;
        $gameReport->valid = false;
        $gameReport->fps = 0;
        $gameReport->oos = false;
        $gameReport->save();

        if ($game->game_report_id === null)
        {
            $gameReport->best_report = true;
            $game->game_report_id = $gameReport->id;
        }
        $gameReport->save();
        $game->save();

        $playerGameReports = array();
        $playerStats = array();

        foreach($result as $key => $value)
        {
            $property = substr($key, 0, -1);

            if ($property == "NAM")
            {
                $id = substr($key, -1);
                $playerGameReports[$id] = new \App\PlayerGameReport();
                $playerGameReports[$id]->game_id = $game->id;
                $playerGameReports[$id]->game_report_id = $gameReport->id;
                $playerHere = \App\Player::where('ladder_id', $ladderId)->where('username', $value["value"])->first();

                if ($playerHere === null)
                    return null;

                $playerGameReports[$id]->player_id = $playerHere->id;

                $playerStats[$id] = new \App\Stats;
                $playerStats[$id]->save();
                $playerGameReports[$id]->stats_id = $playerStats[$id]->id;
            }
        }

        foreach($result as $key => $value)
        {
            $cid = substr($key, -1); // Current Index
            $property = substr($key, 0, -1); // Property without index

            if (is_numeric($cid) && $cid >= 0 && $cid < 8)
            {
                // Save Game Specific Stats like buildings bought, destroyed etc
                if (in_array(strtolower($property), $playerStats[$cid]->gameStatsColumns))
                {
                    $playerStats[$cid]->{strtolower($property)} = json_encode($value);
                }
                $playerGameReports[$cid]->local_id = $cid;
                $playerGameReports[$cid]->local_team_id = $cid;

                switch($property)
                {
                case "CMP":
                    $gameResult = $value["value"];
                    $playerGameReports[$cid]->disconnected =
                        ($gameResult & GameResult::COMPLETION_DISCONNECTED)  ? true : false;
                    $playerGameReports[$cid]->no_completion =
                        ($gameResult & GameResult::COMPLETION_NO_COMPLETION) ? true : false;
                    $playerGameReports[$cid]->quit = ($gameResult & GameResult::COMPLETION_QUIT) ? true : false;
                    $playerGameReports[$cid]->won =  ($gameResult & GameResult::COMPLETION_WON)  ? true : false;
                    $playerGameReports[$cid]->draw = ($gameResult & GameResult::COMPLETION_DRAW) ? true : false;
                    $playerGameReports[$cid]->defeated =
                        ($gameResult & GameResult::COMPLETION_DEFEATED) ? true : false;
                    break;
                case "RSG":
                    $playerGameReports[$cid]->quit = $value["value"];
                case "DED":
                    $playerGameReports[$cid]->defeated = $value["value"];
                    break;
                case "ALY":
                    // Unsupported ATM. My idea is that local_team_id should be the ID of the lowest ALLY -or-yourself
                    // For now everyone is on his own team
                    $playerGameReports[$cid]->local_team_id = $cid;
                    break;
                case "SPC":
                    $playerGameReports[$cid]->spectator = $value["value"];;
                    break;

                case "LCN": //TS lost connection
                case "CON":
                    $playerGameReports[$cid]->disconnected = $value["value"];;
                    break;
                default:
                }
            }

            switch($key)
            {
            case "CMPL":
                // Must be RA, not sure what to do though
                $player->won = !$playerGameReports[$cid]->defeated && !$playerGameReports[$cid]->quit;
                $player->no_completion = false;
                $player->draw = ($value["value"] & GameResult::COMPLETION_DRAW) ? true : false;
                $player->defeated = false;
                break;
            case "OOSY":
                $gameReport->oos = $value["value"];
                break;
            case "SDFX":
                foreach ($playerGameReports as $playerGR)
                {
                    $playerGR->disconnected = $value["value"];
                }
                break;
            case "DURA":
                $gameReport->duration = $value["value"];
                $gameReport->valid = $gameReport->duration > 60;
                break;
            case "AFPS":
                $gameReport->fps = $value["value"];
                break;
            case "QUIT":
                $reporter->quit = $value["value"];
                break;
            case "FINI":
                $gameReport->finished = $value["value"];
                break;
            default:
            }
        }

        foreach ($playerGameReports as $playerGR)
        {
            $playerGR->save();
        }
        foreach ($playerStats as $pStats)
        {
            $pStats->save();
        }
        $gameReport->save();
        $game->save();

        return $gameReport;
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

        if ($game === null)
        {
            $game = new \App\Game();
            $game->ladder_history_id = $ladder->id;
            $game->wol_game_id = $id;
            //$game->save();
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
        if ($result["IDNO"])
            return $result["IDNO"]["value"];
        return null;
    }
}