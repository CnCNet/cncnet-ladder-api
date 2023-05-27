<div class="player-card">
    <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
        <div class="player-avatar">
            @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 100])
        </div>
    </a>

    <div class="player-details">
        <h2 class="username">
            <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
                {{ $player->username }}
            </a>
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
