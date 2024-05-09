@props([
    'clans',
    'history',
    'statsXOfTheDay',
    'ranks'
])
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
        <x-ladder.listing.clan.clan-row
                :history="$history"
                :clan-cache="$clanCache"
                :stats-x-of-the-day="$statsXOfTheDay"
                :ranks="$ranks"
                :most-used-factions="$mostUsedFactions"
        />
    @endforeach
</div>
