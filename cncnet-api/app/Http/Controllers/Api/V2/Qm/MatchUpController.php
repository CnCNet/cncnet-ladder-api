<?php

namespace App\Http\Controllers\Api\V2\Qm;

use App\Helpers\AIHelper;
use App\Helpers\GameHelper;
use App\Helpers\LeagueHelper;
use App\Http\Services\PlayerService;
use App\Http\Services\QuickMatchService;
use App\Http\Services\QuickMatchSpawnService;
use App\Jobs\Qm\FindOpponentJob;
use App\Models\Game;
use App\Models\IpAddress;
use App\Models\Ladder;
use App\Models\MapPool;
use App\Models\Player;
use App\Models\QmCanceledMatch;
use App\Models\QmConnectionStats;
use App\Models\QmMatch;
use App\Models\QmMatchPlayer;
use App\Models\QmMatchState;
use App\Models\QmUserId;
use App\Models\StateType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MatchUpController
{
    private $playerService;
    private $quickMatchService;

    public function __construct(
        PlayerService $playerService,
        QuickMatchService $quickMatchService
    )
    {
        $this->playerService = $playerService;
        $this->quickMatchService = $quickMatchService;
    }

    public function __invoke(Request $request, Ladder $ladder, string $playerName)
    {
        // check that the player is registered in the ladder
        $player = $this->playerService->findPlayerByUsername($playerName, $ladder);
        if (!isset($player))
        {
            return $this->quickMatchService->onFatalError(
                $playerName . ' is not registered in ' . $ladder->abbreviation
            );
        }

        // check that the player is related to the authenticated user
        $user = $request->user();
        if ($user->id !== $player->user->id)
        {
            return $this->quickMatchService->onFatalError(
                'Failed'
            );
        }

        if ($request->hwid)
        {
            QmUserId::createNew($user->id, $request->hwid);
        }

        // @TODO: Add into admin settings for latest hash to check for
        $exeHash = $request->exe_hash;
        $exeHashToCheck = "1d91ff31eb11384bbd3a8d0292dd4fef61837d26";
        Log::info("Exe hash of $user->name : $exeHash");

        if ($ladder->game == "yr" && $exeHash != $exeHashToCheck)
        {
            Log::info("Exe hash mismatch detected, notification sent to $user->name : $exeHash : Should be: $exeHashToCheck");
            return $this->quickMatchService->onFatalError(
                'Please update to the latest version of CnCNet. If already updated, launch the Ranked Match client from the main CnCNet client.'
            );
        }

        # Check player has an active nick to play with, set one if not
        $this->playerService->setActiveUsername($player, $ladder);

        $this->playerService->createPlayerRatingIfNull($player);

        $qmPlayer = QmMatchPlayer::where("player_id", $player->id)
            ->where("waiting", true)
            ->first();

        switch ($request->type)
        {
            case "quit":
                return $this->onQuit($qmPlayer);

            case "update":
                return $this->onUpdate($player, $request);

            case "match me up":
                return $this->onMatchMeUp($request, $ladder, $player, $qmPlayer);

            default:
                return response()->json([
                    "type" => "error",
                    "description" => "unknown type: " . $request->type
                ]);
        }
    }

    /**
     * The player is leaving the queue.
     * Clear up the database and remove the player from the queue.
     * @param QmMatchPlayer $qmPlayer
     * @return JsonResponse
     */
    private function onQuit(?QmMatchPlayer $qmPlayer)
    {
        if (isset($qmPlayer))
        {

            if (isset($qmPlayer->qm_match_id))
            {
                $qmPlayer->qmMatch->save();
            }

            if (isset($qmPlayer->qEntry))
            {
                $qmPlayer->qEntry->delete();
            }

            $qmPlayer->delete();
        }

        return response()->json([
            "type" => "quit"
        ]);
    }


    /**
     * Update the status of the match related to the given player
     * @param Player $player
     * @param $request
     * @return JsonResponse
     */
    private function onUpdate(Player $player, $request)
    {
        $status = $request->status;
        $seed = $request->seed;
        $peers = $request->peers;

        if ($seed)
        {
            $qmMatch = QmMatch::where('seed', '=', $seed)
                ->join('qm_match_players', 'qm_match_id', '=', 'qm_matches.id')
                ->where('qm_match_players.player_id', '=', $player->id)
                ->select('qm_matches.*')
                ->first();

            if (isset($qmMatch))
            {
                if ($status === 'touch')
                {
                    $qmMatch->touch();
                }
                else
                {

                    $qmState = new QmMatchState();
                    $qmState->player_id = $player->id;
                    $qmState->qm_match_id = $qmMatch->id;
                    $qmState->state_type_id = StateType::findByName($status)->id;
                    $qmState->save();

                    //match not ready
                    if ($qmState->state_type_id === 7)
                    {
                        $canceledMatch = new QmCanceledMatch();
                        $canceledMatch->qm_match_id = $qmMatch->id;
                        $canceledMatch->player_id = $player->id;
                        $canceledMatch->ladder_id = $qmMatch->ladder_id;
                        $canceledMatch->save();
                    }

                    if (isset($peers))
                    {
                        foreach ($peers as $peer)
                        {
                            $con = new QmConnectionStats();
                            $con->qm_match_id = $qmMatch->id;
                            $con->player_id = $player->id;
                            $con->peer_id = $peer['id'];
                            $con->ip_address_id = IpAddress::getID($peer['address']);
                            $con->port = $peer['port'];
                            $con->rtt = $peer['rtt'];
                            $con->save();
                        }
                    }
                }

                $qmMatch->save();

                return response()->json(
                    [
                        "message"  => "update qm match: " . $status
                    ]
                );
            }
        }

        return response()->json([
            "type" => "update"
        ]);
    }

    /**
     * This matchup system is restful, a player will have to check in to see if there is a matchup waitin.
     * If there is already a matchup then all these top level ifs will fall through and the game info will be sent.
     * Else we'll try to set up a match.
     *
     * @param Request $request
     * @param Ladder $ladder
     * @param Player $player
     * @param ?QmMatchPlayer $qmPlayer
     */
    private function onMatchMeUp(Request $request, Ladder $ladder, Player $player, ?QmMatchPlayer $qmPlayer)
    {

        Log::debug('Username : ' . $player->username . ' on ladder ' . $ladder->name);
        Log::debug('Match Me Up Request Body : ' . json_encode($request->all()));

        // If we're new to the queue, create required QmMatchPlayer model
        if (!isset($qmPlayer))
        {
            $qmPlayer = $this->quickMatchService->createQMPlayer($request, $player, $ladder->current_history);
            $validSides = $this->quickMatchService->checkPlayerSidesAreValid($qmPlayer, $request->side, $ladder->qmLadderRules);
            $qmPlayer->save();

            if (!$validSides)
            {
                return $this->quickMatchService->onFatalError(
                    'Side (' . $request->side . ') is not allowed'
                );
            }
        }

        // Important check, sent from qm client
        if ($request->ai_dat)
        {
            $qmPlayer->ai_dat = $request->ai_dat;
            $qmPlayer->save();
            return $this->quickMatchService->onFatalError(
                'Error, please contact us on the CnCNet Discord'
            );
        }

        $alert = $this->quickMatchService->checkForAlerts($ladder, $player);

        // Check if the player should now match the AI
        if ($this->playerService->checkPlayerShouldMatchAI($request, $player, $ladder, $qmPlayer))
        {
            // Delete player from queue if they were in one.
            if (isset($qmPlayer->qEntry))
            {
                $qmPlayer->qEntry->delete();
            }

            // Exclude certain maps that do not work with AI well for Blitz
            if ($ladder->abbreviation === GameHelper::$GAME_BLITZ)
            {
                $maps = MapPool::find(63)->maps;
            }
            else
            {
                $maps = $ladder->mapPool->maps;
            }

            $qmMatch = $this->quickMatchService->createQmAIMatch($qmPlayer, LeagueHelper::CONTENDERS_LEAGUE, $maps, Game::GAME_TYPE_1VS1_AI);

            $spawnStruct = QuickMatchSpawnService::createSpawnStruct($qmMatch, $qmPlayer, $ladder, $ladder->qmLadderRules);
            $spawnStruct = QuickMatchSpawnService::addQuickMatchAISpawnIni($spawnStruct, $ladder, AIHelper::BRUTAL_AI);

            return response()->json($spawnStruct);
        }


        if (isset($qmPlayer->qEntry))
        {
            $gameType = $qmPlayer->qEntry->game_type;
        }
        else
        {
            $gameType = Game::GAME_TYPE_1VS1;
            if ($ladder->clans_allowed || $ladder->qmLadderRules->player_count == 4)
            {
                $gameType = Game::GAME_TYPE_2VS2;
            }
        }

        // If no match has been found already, then queue up to match an opponent
        if (!isset($qmPlayer->qm_match_id))
        {
            $qmQueueEntry = $this->quickMatchService->createOrUpdateQueueEntry($player, $qmPlayer, $ladder->current_history, $gameType);

            // Push a job to find an opponent
            Log::debug('Queued FindOpponent job');
            dispatch(new FindOpponentJob($qmQueueEntry?->id, $gameType));

            $qmPlayer->touch();

            return $this->quickMatchService->onCheckback($alert);
        }

        // If we're past this point, a match has been found
        $qmMatch = QmMatch::find($qmPlayer->qm_match_id);

        // Creates the initial spawn.ini to send to client
        $spawnStruct = QuickMatchSpawnService::createSpawnStruct($qmMatch, $qmPlayer, $ladder, $ladder->qmLadderRules);

        // Check we have all players ready before writing them to spawn.ini
        $otherQmMatchPlayers = $qmMatch->players()
            ->where('id', '<>', $qmPlayer->id)
            ->orderBy('color', 'ASC')
            ->get();

        Log::debug('MatchUpController ** count otherQmMatchPlayers : ' . $otherQmMatchPlayers->count());

        if ($otherQmMatchPlayers->count() < $ladder->qmLadderRules->player_count - 1)
        {
            $qmPlayer->waiting = false;
            $qmPlayer->save();
            Log::info("MatchUpController ** Player Check: QMPlayer: $qmPlayer  - QMMatch: $qmMatch");
            return $this->quickMatchService->onCheckback($alert);
        }

        if ($gameType == Game::GAME_TYPE_2VS2_AI)
        {
            // Prepend quick-coop AI ini file
            $spawnStruct = QuickMatchSpawnService::addQuickMatchCoopAISpawnIni($spawnStruct, AIHelper::BRUTAL_AI);
        }

        // if its a 2v2 match but not clan
        if (!$ladder->clans_allowed && $ladder->qmLadderRules->player_count > 2)
        {
            $spawnStruct = QuickMatchSpawnService::appendOthersToSpawnIni($spawnStruct, $qmPlayer, $otherQmMatchPlayers);

            if ($ladder->game == GameHelper::$GAME_RA)
            {
                Log::info("RA1 alliances");
                $spawnStruct = QuickMatchSpawnService::appendRA1AlliancesToSpawnIni($spawnStruct, $qmPlayer, $otherQmMatchPlayers);
            }
            else
            {
                $spawnStruct = QuickMatchSpawnService::appendAlliancesToSpawnIni($spawnStruct, $qmPlayer, $otherQmMatchPlayers);
            }
        }
        else
        {
            // Write the spawn.ini "Others" sections
            $spawnStruct = QuickMatchSpawnService::appendOthersAndTeamAlliancesToSpawnIni($spawnStruct, $qmPlayer, $otherQmMatchPlayers);
        }

        // Write the observers
        $spawnStruct = QuickMatchSpawnService::appendObservers($spawnStruct, $qmPlayer, $otherQmMatchPlayers);

        $qmPlayer->waiting = false;
        $qmPlayer->save();

        return response()->json($spawnStruct);
    }
}
