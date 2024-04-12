<?php

namespace App\Http\Services;

use App\Models\AIPlayer;
use App\Models\Game;
use App\Models\GameReport;
use App\Models\Player;
use App\Models\PlayerGameReport;
use Exception;

class DuneGameService
{
    private $idsToSkip;

    public function __construct()
    {
        $this->idsToSkip = [];
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

            if ($property == "PL_")
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

                $playerUsername = explode("/", $value["value"])[0];
                $playerHere = Player::where('ladder_id', $ladder->id)->where('username', $playerUsername)->first();

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

                if ($isClanLadderGame)
                {
                    $clan = $playerHere->clanPlayer->clan;
                    $playerGameReports[$id]->clan_id = $clan->id;
                }

                $playerGameReports[$id]->save();


                # Player settings
                $playerSettings = explode("/", $value["value"]);
                $playerSide = $playerSettings[1];
                $playerColor = $playerSettings[2];
                $playerHandicap = $playerSettings[3];

                switch ($playerSide)
                {
                    case "Atreides":
                        $playerSide = 0;
                        break;

                    case "Harkonnen":
                        $playerSide = 1;
                        break;

                    case "Ordos":
                        $playerSide = 2;
                        break;
                }

                $playerStats[$id] = new \App\Models\Stats2;
                $playerStats[$id]->player_game_report_id = $playerGameReports[$id]->id;
                $playerStats[$id]->sid = $playerSide;
                $playerStats[$id]->col = $playerColor;
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
                case "ENDS":
                    $gameWon = !$reporter->defeated && !$reporter->quit;

                    $gameResult = $value["value"];
                    switch ($gameResult)
                    {
                        case DuneGameResult::GES_ENDEDNORMALLY:
                            // hmm?
                            break;

                        case DuneGameResult::GES_CONNECTIONLOST:
                            $gameWon = false;
                            break;

                        case DuneGameResult::GES_ISURRENDERED:
                            $gameWon = false;
                            break;

                        case DuneGameResult::GES_OPPONENTSURRENDERED:
                            $gameWon = true;
                            break;

                        case DuneGameResult::GES_OUTOFSYNC:
                            $gameReport->oos = true;
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
                    }

                    foreach ($playerGameReports as $playerGR)
                    {
                        $playerGR->won = !$gameWon;
                        $playerGR->defeated = !$playerGR->won;
                        //$playerGR->no_completion = false;
                    }

                    $reporter->won = $gameWon;
                    //$reporter->no_completion = false;
                    $reporter->defeated = !$reporter->won;
                    break;

                case "SDFX":
                    foreach ($playerGameReports as $playerGR)
                    {
                        $playerGR->disconnected = $value["value"];
                    }
                    break;

                case "TIME":
                    $gameReport->duration = $value["value"];
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

    public function fillGameCols($game, $result)
    {
        $gameSettings = explode(" ", $result["GSET"]["value"]);
        $values = [];

        // Loop through the parts array two items at a time
        for ($i = 0; $i < count($gameSettings); $i += 2)
        {
            if (isset($gameSettings[$i + 1]))
            {
                $values[$gameSettings[$i]] = $gameSettings[$i + 1];
            }
        }
        foreach ($values as $key => $value)
        {
            switch ($key)
            {
                case "Worms":
                    break;
                case "Crates":
                    $game->crat = $value;
                    break;
                case "Credits";
                    $game->cred = $value;
                    break;
                case "Techlevel":
                    break;
            }
        }
        $game->plrs = $result["NUMP"]["value"];
        $game->scen = str_replace("MAP:", "", $result["GMAP"]["value"]);
        $game->wol_game_id = $this->getUniqueGameIdentifier($result);
        $game->save();
    }

    private function getUniqueGameIdentifier($result)
    {
        if (isset($result["GMID"]) && $result["GMID"])
            return $result["GMID"]["value"];
        return null;
    }
}
