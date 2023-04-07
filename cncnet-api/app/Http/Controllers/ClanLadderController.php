<?php

namespace App\Http\Controllers;

use App\Clan;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ClanCache;


class ClanLadderController extends Controller
{
    protected $ladderService = null;

    public function __construct()
    {
        $this->ladderService = new \App\Http\Services\LadderService;
    }

    public function getIndex(Request $request)
    {
        $clanLadders = $this->ladderService->getLatestClanLadders();
        $ladders = $this->ladderService->getLatestLadders();

        return view('clans.index', ['ladders' => $ladders, 'clanLadders' => $clanLadders]);
    }

    public function getListing(Request $request)
    {
        $history = $this->ladderService->getActiveLadderByDate($request->date, $request->game);
        if ($history === null)
        {
            abort(404);
        }

        $clanLadders = $this->ladderService->getLatestClanLadders();
        $ladders = $this->ladderService->getLatestLadders();
        $laddersPrevious = $this->ladderService->getPreviousLaddersByGame($request->date, $request->game);
        $search = $request->search;

        # Default
        $clans = ClanCache::where("ladder_history_id", "=", $history->id)
            ->where("clan_name", "like", "%" . $request->search . "%")
            ->orderBy("points", "desc")
            ->paginate(45);

        return view('clans.listing', [
            'clanLadders' => $clanLadders,
            'clans' => $clans,
            'ladders' => $ladders,
            'history' => $history,
        ]);
    }

    public function getLadderClan(Request $request, $date = null, $cncnetGame = null, $clanName = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);

        if ($history == null)
        {
            abort(404, "No clan ladder found");
        }

        $clan = Clan::where("short", $clanName)
            ->where("ladder_id", $history->ladder->id)
            ->first();

        if ($clan == null)
        {
            abort(404, "No clan found");
        }

        $clanCache = ClanCache::where("ladder_history_id", $history->id)
            ->where("clan_id", $clan->id)
            ->first();

        $games = $clanCache->clan->clanGames()
            ->where("ladder_history_id", "=", $history->id)
            ->orderBy('created_at', 'DESC')
            ->paginate(24);

        // $ladderPlayer = $this->ladderService->getLadderPlayer($history, $player->username);
        // $userPlayer = User::where("id", $clan->user_id)->first();
        // $userTier = $player->getCachedPlayerTierByLadderHistory($history);

        // # Stats
        // $graphGamesPlayedByMonth = $this->chartService->getGamesPlayedByMonth($player, $history);
        // $playerFactionsByMonth = $this->statsService->getFactionsPlayedByPlayer($player, $history);
        // $playerWinLossByMaps = $this->statsService->getMapWinLossByPlayer($player, $history);
        // $playerGamesLast24Hours = $player->totalGames24Hours($history);
        // $playerMatchups = $this->statsService->getPlayerMatchups($player, $history);
        // $playerOfTheDayAward = $this->statsService->checkPlayerIsPlayerOfTheDay($history, $player);
        // $recentAchievements = $this->achievementService->getRecentlyUnlockedAchievements($history, $userPlayer, 3);
        // $achievementProgressCounts = $this->achievementService->getProgressCountsByUser($history, $userPlayer);

        return view(
            "clans.clan-detail",
            [
                "history" => $history,
                "clan" => $clanCache,
                "games" => $games,
            ]
        );
    }
}
