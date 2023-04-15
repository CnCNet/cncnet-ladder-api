<div class="player-card">
    <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
        <div class="player-avatar">
            @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 50])
        </div>
    </a>

    <div class="player-details">
        <h2 class="username">
            @if ($isClanGame)
                <a href="{{ \App\URLHelper::getClanProfileLadderUrl($history, $player->clanPlayer->clan->id) }}"
                    title="View {{ $player->clanPlayer->clan->short }}'s clan profile">
                    {{ $clanCache->clan_name }}

                    <br />
                    <span style="font-size: 1rem">Played by: {{ $player->username }}</span>
                </a>
            @else
                <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
                    {{ $player->username }}
                </a>
            @endif
        </h2>

        <h5 class="rank pb-1">
            Rank #{{ $playerRank }}
            <br />

            <div style="font-size: 0.8rem" class="mt-2 mb-2">
                <?php $tier = $player->getCachedPlayerTierByLadderHistory($history); ?>
                {!! \App\Helpers\LeagueHelper::getLeagueIconByTier($tier) !!}
                - {{ \App\Helpers\LeagueHelper::getLeagueNameByTier($tier) }}
            </div>
        </h5>

        <div class="d-flex">
            <div class="faction">
                @if ($pgr->stats)
                    @php $playerStats2 = \App\Stats2::where("id", $pgr->stats->id)->first(); @endphp
                    @php $playerCountry = $playerStats2->faction($history->ladder->game, $pgr->stats->cty); @endphp
                    <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
                @endif
            </div>
            <div class="points {{ $pgr->won ? 'won' : 'lost' }}">
                @if ($pgr->points >= 0)
                    <span>{{ '+' }}</span>
                @endif
                {{ $pgr->points }}
            </div>
        </div>
    </div>
</div>
