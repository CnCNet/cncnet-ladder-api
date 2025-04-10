<?php

namespace App\Http\Services;

use App\Models\AIPlayer;
use App\Models\Game;
use App\Models\GameClip;
use App\Models\GameReport;
use App\Models\Player;
use App\Models\PlayerGameReport;
use Exception;
use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GameService
{
    private $maxPlayers;
    private $playerService;
    private $idsToSkip;

    public function __construct()
    {
        $this->maxPlayers = 8;
        $this->idsToSkip = [];
        $this->playerService = new PlayerService();
    }

    /**
     * Some stat indexes for houses we should skip
     * @param mixed $id 
     * @return bool 
     */
    private function shouldSkipStatsIndex($id)
    {
        return in_array($id, $this->idsToSkip);
    }

    /**
     * 
     * @param mixed $result 
     * @param mixed $gameId 
     * @param mixed $playerId 
     * @param mixed $ladder 
     * @param mixed $cncnetGame 
     * @return (string|null)[]|GameReport[] 
     * @throws Exception 
     */
    public function saveGameStats($result, $gameId, $playerId, $ladder, $cncnetGame)
    {
        $game = Game::where("id", "=", $gameId)->first();
        $player = Player::where("id", "=", $playerId)->first();

        if ($player == null)
        {
            return ['error' => 'player not found', 'gameReport' => null];
        }

        $isClanLadderGame = $ladder->clans_allowed;

        $reporter = null;

        $gameReport = new GameReport;
        $gameReport->game_id = $game->id;
        $gameReport->player_id = $playerId;

        if ($isClanLadderGame)
        {
            $clanPlayer = $player->clanPlayer;
            if ($clanPlayer)
            {
                $clan = $clanPlayer->clan;
                $gameReport->clan_id = $clan->id;
            }
        }

        $gameReport->best_report = false;
        $gameReport->manual_report = false;
        $gameReport->duration = 0;
        $gameReport->valid = true;
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

        $playerGameReports = [];
        $playerStats = [];

        foreach ($result as $key => $value)
        {
            $property = substr($key, 0, -1);

            if ($property == "NAM")
            {
                $id = substr($key, -1);

                if ($value["value"] == "Special" || $value["value"] == "Neutral")
                {
                    $this->idsToSkip[] = $id;
                    continue;
                }

                $playerGameReports[$id] = new PlayerGameReport();
                $playerGameReports[$id]->game_id = $game->id;
                $playerGameReports[$id]->game_report_id = $gameReport->id;

                if ($value["value"] == "Computer")
                {
                    $playerHere = AIPlayer::getAIPlayer($ladder->currentHistory());
                }
                else if ($value["value"] == "<human player>")
                {
                    $playerHere = $player;
                }
                else if ($value["value"] !== "Special" || $value["value"] !== "Neutral")
                {
                    $playerHere = Player::where('ladder_id', $ladder->id)->where('username', $value["value"])->first();
                }

                if ($playerHere === null)
                {
                    return [
                        'error' => 'playerHere is null for username ' . json_decode($value["value"]), 'gameReport' => null
                    ];
                }

                if ($playerHere->id == $playerId)
                {
                    $reporter = $playerGameReports[$id];
                }

                $playerGameReports[$id]->player_id = $playerHere->id;

                $playerGameReports[$id]->team = $game->qmMatch->findQmPlayerByPlayerId($playerHere->id)?->team;

                if ($isClanLadderGame)
                {
                    $clan = $playerHere->clanPlayer->clan;
                    $playerGameReports[$id]->clan_id = $clan->id;
                }

                $playerGameReports[$id]->save();

                $playerStats[$id] = new \App\Models\Stats2;
                $playerStats[$id]->player_game_report_id = $playerGameReports[$id]->id;
                $playerStats[$id]->save();

                $playerGameReports[$id]->stats_id = $playerStats[$id]->id;
                $playerGameReports[$id]->save();
            }
        }

        # Save Game Specific Stats like buildings bought, destroyed etc
        foreach ($ladder->countableGameObjects as $countable)
        {
            foreach ($playerStats as $k => $value)
            {
                if (
                    array_key_exists($countable->heap_name . "$k", $result) &&
                    array_key_exists("counts", $result[$countable->heap_name . "$k"])
                )
                {
                    $objects = $result[$countable->heap_name . "$k"]["counts"];

                    if (array_key_exists($countable->heap_id, $objects))
                    {
                        $goc = new \App\Models\GameObjectCounts;
                        $goc->stats_id = $value->id;
                        $goc->countable_game_objects_id = $countable->id;
                        $goc->count = $objects[$countable->heap_id];
                        $goc->save();
                    }
                }
            }
        }

        foreach ($result as $key => $value)
        {
            $cid = substr($key, -1); // Current Index
            $property = substr($key, 0, -1); // Property without index

            if ($this->shouldSkipStatsIndex($cid))
            {
                continue;
            }

            if (is_numeric($cid) && $cid >= 0 && $cid < 8)
            {
                if (isset($playerStats[$cid]) && in_array(strtolower($property),  $playerStats[$cid]->gameStatsColumns))
                {
                    $playerStats[$cid]->{strtolower($property)} = $value["value"];
                }
                $playerGameReports[$cid]->local_id = $cid;
                $playerGameReports[$cid]->local_team_id = $cid;

                switch ($property)
                {
                    case "CMP":
                        $gameResult = $value["value"];
                        $playerGameReports[$cid]->disconnected = ($gameResult & GameResult::COMPLETION_DISCONNECTED) ? true : false;
                        $playerGameReports[$cid]->no_completion = ($gameResult & GameResult::COMPLETION_NO_COMPLETION) ? true : false;
                        $playerGameReports[$cid]->quit = ($gameResult & GameResult::COMPLETION_QUIT) ? true : false;
                        $playerGameReports[$cid]->won =  ($gameResult & GameResult::COMPLETION_WON)  ? true : false;
                        $playerGameReports[$cid]->draw = ($gameResult & GameResult::COMPLETION_DRAW) ? true : false;
                        $playerGameReports[$cid]->defeated = ($gameResult & GameResult::COMPLETION_DEFEATED) ? true : false;
                        break;

                    case "RSG":
                        $playerGameReports[$cid]->quit = $value["value"];
                        break;

                    case "DED":
                        $playerGameReports[$cid]->defeated = $value["value"];
                        break;

                    case "ALY":
                        // Unsupported ATM. My idea is that local_team_id should be the ID of the lowest ALLY -or-yourself
                        // For now everyone is on his own team
                        $playerGameReports[$cid]->local_team_id = $cid;
                        break;

                    case "SPC":
                        $playerGameReports[$cid]->spectator = $value["value"];
                        break;

                    case "LCN": // TS lost connection
                    case "CON":
                        $playerGameReports[$cid]->disconnected = $value["value"];
                        break;

                    case "BSP": // starting spawn
                        $playerGameReports[$cid]->spawn = $value["value"];
                        break;

                    case "SID": // hack for Red Alert
                        if (!is_numeric($value["value"]))
                        {
                            switch ($value["value"])
                            {
                                case "SPA":
                                    $playerStats[$cid]->sid = 0;
                                    break;
                                case "GRE":
                                    $playerStats[$cid]->sid = 1;
                                    break;
                                case "USS":
                                    $playerStats[$cid]->sid = 2;
                                    break;
                                case "ENG":
                                    $playerStats[$cid]->sid = 3;
                                    break;
                                case "ITA":
                                    $playerStats[$cid]->sid = 4;
                                    break;
                                case "GER":
                                    $playerStats[$cid]->sid = 5;
                                    break;
                                case "FRA":
                                    $playerStats[$cid]->sid = 6;
                                    break;
                                case "TKY":
                                    $playerStats[$cid]->sid = 7;
                                    break;
                                default:
                                    break;
                            }
                        }
                    default:
                }

                $playerGameReports[$cid]->save();
            }

            switch ($key)
            {
                case "CMPL":
                    // Must be RA, not sure what to do though
                    if ($value["value"] == GameResult::COMPLETION_DRAW)
                    {
                        foreach ($playerGameReports as $playerGR)
                        {
                            $playerGR->draw = true;
                            $playerGR->won = false;
                            $playerGR->defeated = false;
                            $playerGR->no_completion = false;
                        }
                    }
                    else
                    {
                        $gameWon = !$reporter->defeated && !$reporter->quit;

                        foreach ($playerGameReports as $playerGR)
                        {
                            $playerGR->won = !$gameWon;
                            $playerGR->defeated = !$playerGR->won;
                            $playerGR->no_completion = false;
                        }

                        $reporter->won = $gameWon;
                        $reporter->no_completion = false;
                        $reporter->defeated = !$reporter->won;
                    }
                    break;

                case "OOSY":
                    $gameReport->oos = $value["value"];
                    if ($gameReport->oos)
                    {
                        // If the game recons then the reporter marks himself as winner, admin will sort it out later
                        foreach ($playerGameReports as $playerGR)
                        {
                            $playerGR->won = false;
                        }
                        $reporter->won = true;
                    }
                    break;

                case "SDFX":
                    foreach ($playerGameReports as $playerGR)
                    {
                        $playerGR->disconnected = $value["value"];
                    }
                    break;

                case "DURA":
                    $gameReport->duration = $value["value"];
                    break;

                case "AFPS":
                    $gameReport->fps = $value["value"];
                    break;

                case "QUIT":
                    if ($reporter !== null && $cncnetGame != "ra")
                    {
                        $reporter->quit = $value["value"];
                    }
                    $gameReport->finished = !$value["value"];
                    break;

                case "FINI":
                    $gameReport->finished = $value["value"];
                    break;

                default:
            }

            $gameReport->save();
        }

        foreach ($playerStats as $pStats)
        {
            $pStats->save();
        }

        foreach ($playerGameReports as $p)
        {
            $p->save();
        }

        $reporter->save();
        $gameReport->save();
        $game->save();

        return ['gameReport' =>  $gameReport];
    }

    public function saveRawStats($result, $gameId, $ladderId)
    {
        $raw = new \App\Models\GameRaw();
        try
        {
            $raw->packet = json_encode($result);
        }
        catch (Exception $e)
        {
            $raw->packet = false;
        }
        if ($raw->packet == false)
        {
            switch (json_last_error())
            {
                case JSON_ERROR_NONE:
                    error_log('saveRawStats - No errors');
                    break;
                case JSON_ERROR_DEPTH:
                    error_log('saveRawStats - Maximum stack depth exceeded');
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    error_log('saveRawStats - Underflow or the modes mismatch');
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    error_log('saveRawStats - Unexpected control character found');
                    break;
                case JSON_ERROR_SYNTAX:
                    error_log('saveRawStats - Syntax error, malformed JSON');
                    break;
                case JSON_ERROR_UTF8:
                    error_log('saveRawStats - Malformed UTF-8 characters, possibly incorrectly encoded');
                    break;
                default:
                    error_log('saveRawStats - Unknown error');
                    break;
            }
        }

        $raw->game_id = $gameId;
        $raw->ladder_id = $ladderId;
        $raw->save();

        return $raw;
    }

    // Credit: https://github.com/dkeetonx
    public function processStatsDmp($file, $cncnetGame, $ladder)
    {
        if ($file == null)
            return null;

        $fh = fopen($file, "r");
        $data = fread($fh, 4);

        if (!$data)
        {
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

                if ($pad > 0)
                {
                    fread($fh, $pad);
                }

                $fieldValueArr = $this->getFieldValue($ttl, $data);
                $result[$ttl["tag"]] = ["tag" => $ttl["tag"], "length" => $ttl["length"], "raw" => base64_encode($fieldValueArr["raw"]), "value" => $fieldValueArr["val"]];
            }
        }

        $types = $ladder->countableGameObjects()->groupBy("heap_name")->get();
        foreach ($types as $type)
        {
            $tag = $type->heap_name;

            for ($i = 0; $i < 8; $i++)
            {
                if (isset($result["$tag$i"]))
                {
                    $raw = base64_decode($result["$tag$i"]["raw"]);
                    $length = $result["$tag$i"]["length"];

                    for ($j = 0, $t = 0; $j < $length; $j += 4, ++$t)
                    {
                        $count = unpack("N", substr($raw, $j, 4))[1];
                        if ($count > 0)
                        {
                            $result["$tag$i"]["counts"][$t] = $count;
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
                //Make sure we only allow visual ascii characters and replace bad chars with ?
                $response["val"] = preg_replace('/[^\x20-\x7e]/', '?', $v[1]);
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

        $game = \App\Models\Game::where("wol_game_id", "=", $id)->first();

        if ($game === null)
        {
            $game = new \App\Models\Game();
            $game->ladder_history_id = $ladder->id;
            //$game->save();
        }
        $this->fillGameCols($game, $result);
        return $game;
    }

    public function fillGameCols($game, $result)
    {
        $game->wol_game_id = $this->getUniqueGameIdentifier($result);
        foreach ($result as $key => $value)
        {
            $gameProperty = substr($key, -1);

            if (!is_numeric($gameProperty))
            {
                // Save Game Details like average fps, out of sync errors etc
                if (in_array(strtolower($key), \App\Models\Game::$gameColumns))
                {
                    $game->{strtolower($key)} = $value["value"];
                }
            }
        }
        $game->save();
    }

    private function getUniqueGameIdentifier($result)
    {
        if (isset($result["IDNO"]) && $result["IDNO"])
            return $result["IDNO"]["value"];
        return null;
    }

    /**
     * 
     * @param Request $request 
     * @return mixed 
     */
    public function uploadGameClip(Request $request)
    {
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();

        // Store the file in the 'videos' directory
        $filePath = $file->storeAs('videos', $fileName, 'public');
        return $filePath;
    }

    /**
     * 
     * @param string $gameId 
     * @param string $playerId 
     * @param string $userId 
     * @param string $clipFilename 
     * @return GameClip 
     */
    public function saveGameClip(string $gameId, string $playerId, string $userId, string $clipFilename)
    {
        $gameClip = new GameClip();
        $gameClip->game_id = $gameId;
        $gameClip->player_id = $playerId;
        $gameClip->user_id = $userId;
        $gameClip->clip_filename = $clipFilename;
        $gameClip->save();
        return $gameClip;
    }
}
