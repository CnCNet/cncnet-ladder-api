<?php

namespace App\Extensions\Qm\Matchup;

use App\Models\QmQueueEntry;
use Illuminate\Support\Facades\Log;

class ClanMatchupHandler extends BaseMatchupHandler
{
    public function matchup(): void
    {
        Log::info("ClanMatchupHandler ** Started");

        // its 2v2 so we need 2 clans
        $numberOfClanRequired = 2;

        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;
        $ladderMaps = $ladder->mapPool->maps;

        $currentPlayer = $this->qmPlayer->player;
        $playerCountPerClanRequired = floor($ladderRules->player_count / $numberOfClanRequired); # (2) for a 2v2
        $playerCountForMatchup = $ladderRules->player_count; # (4) for a 2v2

        # Fetch all entries who are currently in queue for this ladder
        $allQMQueueEntries = $this->quickMatchService->fetchQmQueueEntry($this->history);

        // get all observers from qm queue entries (maximum of one observer per match)
        // Prioritize observers who have been waiting the longest
        $observersQmQueueEntries = $allQMQueueEntries
            ->filter(function($qmQueueEntry) {
                return $qmQueueEntry->qmPlayer->isObserver();
            })
            ->sortBy('created_at')
            ->take(1);
        $this->matchHasObservers = $observersQmQueueEntries->count() > 0;

        Log::info("ClanMatchupHandler ** Players Per Clan Required: " . $playerCountPerClanRequired);
        Log::info("ClanMatchupHandler ** Players For Matchup Required: " . $playerCountForMatchup);
        Log::info("ClanMatchupHandler ** Match Has Observer Present: " . ($this->matchHasObservers ? 'yes' : 'no'));

        // if a player has no clan, then remove him from the queue
        if (!isset($currentPlayer->clanPlayer))
        {
            Log::info("ClanMatchupHandler ** Clan Player Null, removing $currentPlayer from queue");
            $this->removeQueueEntry();
            return;
        }

        $groupedQmQueueEntriesByClan = $allQMQueueEntries
            // filter out observers
            ->reject(function($qmQueueEntry) {
                return $qmQueueEntry->qmPlayer->isObserver();
            })
            // group all qm queue entries by clan
            ->groupBy(function($qmQueueEntry) {
                return $qmQueueEntry->qmPlayer->clan_id;
            })
            // filter out clans that don't have enough players
            ->reject(function($clanQmQueueEntries) use ($playerCountPerClanRequired) {
                return $clanQmQueueEntries->count() < $playerCountPerClanRequired;
            });


        //Log::info("ClanMatchupHandler ** groupedQmQueueEntriesByClan " . print_r($groupedQmQueueEntriesByClan, true));


        // now $groupedQmQueueEntriesByClan is a collection of clan with at least 2 players per clan

        // if there is not enough clan ready, then exit
        if($groupedQmQueueEntriesByClan->count() < $numberOfClanRequired)
        {
            Log::info("ClanMatchupHandler ** There is " . $groupedQmQueueEntriesByClan->count() . " clans ready, but we need $numberOfClanRequired clans");
            Log::info("ClanMatchupHandler ** There is " . $allQMQueueEntries->count() . " queue entries");
            Log::info("ClanMatchupHandler ** Not enough clans/players in queue, exiting...");
            return;
        }

        // we need to find the clan that has the current player in it
        $currentPlayerClan = $groupedQmQueueEntriesByClan->filter(function($clanQmQueueEntries) use ($currentPlayer) {
            return $clanQmQueueEntries->filter(function($qmQueueEnitry) use ($currentPlayer) {
                    return $qmQueueEnitry->id == $this->qmQueueEntry->id;
                })->count() === 1;
        })->take(1);
        $currentPlayerClanClanId = $currentPlayer->clanPlayer->clan_id;

        Log::info("ClanMatchupHandler ** Current player clan id: " . $currentPlayerClanClanId);

        // and we need to find $numberOfClanRequired - 1 other clans
        // lets just exclude the currentPlayerClan and remove from the list players that are already in currentPlayerClan
        $otherClans = $groupedQmQueueEntriesByClan
            // remove currentPlayerClan from the collection
            ->reject(function($clanQmQueueEntries, $clanId) use ($currentPlayerClanClanId, $currentPlayerClan) {
                return $clanId == $currentPlayerClanClanId;
            });

        // remove players that are already in currentPlayerClan from other clans
        foreach($otherClans as $clanId => $clanQmQueueEntries)
        {
            $otherClans[$clanId] = $clanQmQueueEntries->reject(function($qmQueueEntry) use ($currentPlayerClan) {
                return $currentPlayerClan->flatten(1)->pluck('id')->contains($qmQueueEntry->id);
            });
        }

        // remove clans that don't have enough players
        $otherClans->reject(function($clanQmQueueEntries) use ($playerCountPerClanRequired) {
            return $clanQmQueueEntries->count() < $playerCountPerClanRequired;
        });

        foreach($otherClans as $clanId => $clanQmQueueEntries)
        {
            if($clanQmQueueEntries->count() > $playerCountPerClanRequired) {
                Log::info("ClanMatchupHandler ** There is " . $clanQmQueueEntries->count() . " players in clan id = " . $clanId . ", but we need only $playerCountPerClanRequired players");
                Log::info("ClanMatchupHandler ** Taking $playerCountPerClanRequired players randomly from clan id = " . $clanId);
                $players = $clanQmQueueEntries->random($playerCountPerClanRequired);
                $otherClans[$clanId] = $players instanceof \Illuminate\Support\Collection ? $players : collect([$players]);
            }
        }


        //Log::info("ClanMatchupHandler ** otherclan 1st " . print_r($otherClans, true));

        // if there is not enough other clan ready, then exit
        if($otherClans->count() < $numberOfClanRequired -1)
        {
            Log::info("ClanMatchupHandler ** There is " . $otherClans->count() . " other clans ready, but we need " . ($numberOfClanRequired - 1) . " other clans");
            Log::info("ClanMatchupHandler ** Not enough other clans/players in queue, exiting...");
            return;
        }

        // if there is more than $numberOfClanRequired - 1 clan ready, then randomly take $numberOfClanRequired - 1 clans
        if($otherClans->count() > $numberOfClanRequired - 1)
        {
            Log::info("ClanMatchupHandler ** There is " . ($otherClans->count() + 1) . " clans ready, but we need only $numberOfClanRequired clans");
            Log::info("ClanMatchupHandler ** Taking $numberOfClanRequired clans randomly");

            //Log::info("ClanMatchupHandler ** otherClans before random " . print_r($otherClans, true));

            // choose a random clan
            $selectedClanIds = $otherClans->keys()->random($numberOfClanRequired - 1);
            if(! $selectedClanIds instanceof \Illuminate\Support\Collection)
            {
                $selectedClanIds = collect([$selectedClanIds]);
            }

            // extract the selected clans and store them into $otherClans
            $selectedClan = collect([]);
            foreach ($selectedClanIds as $selectedClanId)
            {
                $selectedClan->put($selectedClanId, $otherClans->get($selectedClanId));
            }
            $otherClans = $selectedClan;

            //Log::info("ClanMatchupHandler ** otherClans class name " . get_class($otherClans));
            //Log::info("ClanMatchupHandler ** otherClans " . print_r($otherClans, true));
        }

        // Log::info("ClanMatchupHandler ** currentPlayerClan " . print_r($currentPlayerClan, true));
        // now $groupedQmQueueEntriesByClan is a collection of clan that is ready for matchup
        // and the current player is in one of these clans
        // and all players are unique and in only one clan
        $otherClans[$currentPlayerClanClanId] = $currentPlayerClan[$currentPlayerClanClanId];
        $groupedQmQueueEntriesByClan = $otherClans;

        //Log::info("ClanMatchupHandler ** groupedQmQueueEntriesByClan " . print_r($groupedQmQueueEntriesByClan, true));

        // get a collection with all players ready (without current player)
        $readyQmQueueEntries = $groupedQmQueueEntriesByClan
            ->flatten(1);
        $readyQmQueueEntries = $readyQmQueueEntries->values()
            ->filter(function($qmQueueEntry) use ($currentPlayer) {
                return $qmQueueEntry->qmPlayer->player->id != $currentPlayer->id;
            });

        $playersReadyCount = $readyQmQueueEntries->count() + 1;
        Log::info("ClanMatchUpHandler ** Player count for matchup: Ready: " . $playersReadyCount . "  Required: " . $playerCountForMatchup);
        Log::info("ClanMatchUpHandler ** Observers count for matchup: " . $observersQmQueueEntries->count());

        // Find common maps of all players
        $commonQmMaps = $this->quickMatchService->getCommonMapsForPlayers($ladder, $readyQmQueueEntries->concat([$this->qmQueueEntry]));

        if (count($commonQmMaps) <= 0) {
            Log::info("ClanMatchUpHandler ** 0 commonQmMaps found, exiting...");
            return;
        }

        $playerNames = implode(",", $this->getPlayerNamesInQueue($readyQmQueueEntries));
        Log::info("Launching clan match with players $playerNames, " . $currentPlayer->username);
        Log::info("    with oberservers: " . ($this->matchHasObservers ? 'yes' : 'no'));

        // Add observers to our ready qm entries so they will be added to the match
        $observersQmQueueEntries->each(function($qmQueueEntry) use ($readyQmQueueEntries) {
            $readyQmQueueEntries->push($qmQueueEntry);
        });

        $this->createMatch(
            $commonQmMaps,
            $readyQmQueueEntries
        );

    }

    public static function getPlayerNamesInQueue($readyQMQueueEntries)
    {
        $playerNames = [];

        foreach ($readyQMQueueEntries as $readyQMQueueEntry)
        {
            $playerNames[] = $readyQMQueueEntry->qmPlayer->player->username;
        }

        return $playerNames;
    }

}
