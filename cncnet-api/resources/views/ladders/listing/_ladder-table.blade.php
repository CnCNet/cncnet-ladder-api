<div class="ladder-player-listing" id="listing">
    <div class="player-row-header">
        <div class="player-rank">
            Rank
        </div>
        <div class="player-avatar">
            Name
        </div>
        <div class="player-social">
            Social
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

    @foreach ($players as $k => $playerCache)
        @include('ladders.listing._player-row', [
            'username' => $playerCache->player_name,
            'points' => $playerCache->points,
            'rank' => $playerCache->rank(),
            'wins' => $playerCache->wins,
            'losses' => $playerCache->games - $playerCache->wins,
            'totalGames' => $playerCache->games,
            'game' => $history->ladder->game,
            'mostPlayedFaction' => $playerCache->mostPlayedFactionNameByLadderHistory($history),
            'url' => \App\URLHelper::getPlayerProfileUrl($history, $playerCache->player_name),
            'avatar' => $playerCache->player->user->getUserAvatar(),
            'twitch' => $playerCache->player->user->getTwitchProfile(),
            'youtube' => $playerCache->player->user->getYouTubeProfile(),
            'discord' => $playerCache->player->user->getDiscordProfile(),
        ])
    @endforeach
</div>
