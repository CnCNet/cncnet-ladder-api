<div class="ladder-player-listing" id="listing">
    <div class="player-row-header">
        <div class="player-rank">
            Rank
        </div>
        <div class="player-avatar">
            Clan Name
        </div>
        <div class="player-social">
        </div>
        <div class="player-points">Points</div>
        <div class="player-wins">Won</div>
        <div class="player-losses">Lost</div>

        @if (request()->input('orderBy') == 'desc')
            <a class="player-games filter-link d-flex text-decoration-none" href="?filterBy=games&orderBy=asc#listing">
                Games
                <span class="material-symbols-outlined ms-1">
                    expand_less
                </span>
            </a>
        @else
            <a class="player-games filter-link d-flex text-decoration-none" href="?filterBy=games&orderBy=desc#listing">
                Games
                <span class="material-symbols-outlined ms-1">
                    expand_more
                </span>
            </a>
        @endif
    </div>

    @foreach ($clans as $k => $clanCache)
        @include('ladders.listing.clan._table-row', [
            'username' => $clanCache->clan_name,
            'points' => $clanCache->points,
            'rank' => $clanCache->rank(),
            'wins' => $clanCache->wins,
            'losses' => $clanCache->games - $clanCache->wins,
            'totalGames' => $clanCache->games,
            'game' => $history->ladder->game,
            'abbreviation' => $history->ladder->abbreviation,
            'mostPlayedFaction' => $clanCache->mostPlayedFactionNameByLadderHistory($history),
            'url' => \App\Models\URLHelper::getClanProfileLadderUrl($history, $clanCache->clan_id),
            'avatar' => $clanCache->getClanAvatar(),
            'twitch' => null,
            'youtube' => null,
            'discord' => null,
        ])
    @endforeach
</div>
