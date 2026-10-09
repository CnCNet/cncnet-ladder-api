<?php

namespace App\Http\Controllers\Api\V2\Qm;

use App\Http\Services\CasualMatchmakingService;
use App\Http\Services\PlayerService;
use App\Http\Services\QuickMatchService;
use App\Models\Game;
use App\Models\Ladder;
use App\Models\Player;
use App\Models\QmMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Casual matchmaking requests from the CnCNet client. Casual players do not log in and
 * can only queue on casual ladders; ranked quick match is handled by MatchUpController.
 * Ladder bans only apply to ranked quick match, so like custom games, casual matchmaking does not check them.
 */
class CasualMatchUpController
{
    private CasualMatchmakingService $casualService;
    private PlayerService $playerService;
    private QuickMatchService $quickMatchService;

    public function __construct(
        CasualMatchmakingService $casualService,
        PlayerService $playerService,
        QuickMatchService $quickMatchService
    )
    {
        $this->casualService = $casualService;
        $this->playerService = $playerService;
        $this->quickMatchService = $quickMatchService;
    }

    public function __invoke(Request $request, Ladder $ladder, string $playerName): JsonResponse
    {
        if (!$ladder->is_casual)
        {
            return $this->quickMatchService->onFatalError('Casual matchmaking is not available on ' . $ladder->abbreviation);
        }

        $player = $this->playerService->findPlayerByUsername($playerName, $ladder);

        // Players that belong to a registered account can only be used by that account, which cannot log in here
        if ($player !== null && !$this->casualService->isCasualAccount($player->user))
        {
            return $this->quickMatchService->onFatalError(
                $playerName . ' is already used by a registered player on ' . $ladder->abbreviation . '. Please choose another name.'
            );
        }

        // Casual players do not log in. A search belongs to the client that started it, which sends the same
        // random token with every request, so that nobody else can use the name to cancel the search or to
        // receive its match.
        $searchToken = (string)$request->input('search_token', '');

        if ($request->input('type') === 'quit')
        {
            if ($player !== null)
            {
                $this->casualService->leaveQueue($player, $searchToken);
            }

            return response()->json(['type' => 'quit']);
        }

        if ($request->input('type') !== 'match me up')
        {
            return response()->json([
                'type' => 'error',
                'description' => 'unknown type: ' . $request->input('type'),
            ]);
        }

        if (!$this->casualService->isValidSearchToken($searchToken))
        {
            return $this->quickMatchService->onFatalError('Casual matchmaking requests need a search token');
        }

        if ($player === null)
        {
            if (!$this->casualService->isValidPlayerName($playerName))
            {
                return $this->quickMatchService->onFatalError(
                    'Player names must be at most 16 characters long, may only contain letters, numbers and -_[]{}^`|\\ and cannot start with a number or hyphen'
                );
            }

            $player = $this->casualService->createPlayer($ladder, $playerName, $request->ip());
            if ($player === null)
            {
                return $this->quickMatchService->onFatalError('Too many new player names were used from your connection. Please try again later.');
            }
        }

        return $this->onMatchMeUp($request, $ladder, $player, $searchToken);
    }

    private function onMatchMeUp(Request $request, Ladder $ladder, Player $player, string $searchToken): JsonResponse
    {
        $qmPlayer = $this->casualService->findWaitingQmPlayer($player);

        if ($qmPlayer !== null && !$this->casualService->checkSearchToken($qmPlayer, $searchToken))
        {
            return $this->quickMatchService->onFatalError(
                $player->username . ' is already searching for a match. If this is you, please try again in a minute.'
            );
        }

        if ($qmPlayer === null)
        {
            $qmPlayer = $this->casualService->createQmPlayer($request, $player, $ladder);
            if ($qmPlayer === null)
            {
                return $this->quickMatchService->onFatalError('Side (' . $request->input('side') . ') is not allowed');
            }

            $this->casualService->startSearch($qmPlayer, $searchToken);
        }

        if ($qmPlayer->qm_match_id === null)
        {
            $gameType = $ladder->qmLadderRules->player_count > 2 ? Game::GAME_TYPE_2VS2 : Game::GAME_TYPE_1VS1;
            $qmQueueEntry = $this->quickMatchService->createOrUpdateQueueEntry($player, $qmPlayer, $ladder->current_history, $gameType);

            $this->casualService->findMatch($qmQueueEntry, $gameType);

            // If this player was matched now, the spawn is sent right away
            $qmPlayer->refresh();
            if ($qmPlayer->qm_match_id === null)
            {
                $qmPlayer->touch();

                return $this->pleaseWait();
            }
        }

        $qmMatch = QmMatch::find($qmPlayer->qm_match_id);

        // The player is put back in the queue on the next checkback
        if (!$this->casualService->canStartMatch($qmMatch, $qmPlayer, $ladder))
        {
            $qmPlayer->waiting = false;
            $qmPlayer->save();

            return $this->pleaseWait();
        }

        $spawnStruct = $this->casualService->createSpawnStruct($qmMatch, $qmPlayer, $ladder);

        $qmPlayer->waiting = false;
        $qmPlayer->save();

        return response()->json($spawnStruct);
    }

    private function pleaseWait(): JsonResponse
    {
        return response()->json([
            'type' => 'please wait',
            'checkback' => CasualMatchmakingService::CHECKBACK_SECONDS,
            'no_sooner_than' => CasualMatchmakingService::CHECKBACK_SECONDS,
        ]);
    }

    public function queueCounts(): JsonResponse
    {
        return response()->json($this->casualService->getQueueCounts());
    }
}
