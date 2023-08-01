<div class="player-card">
    <div class="player-avatar" style="width:120px;height:120px;">
        <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">

            @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 120])
        </a>
    </div>

    <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">

        <div class="h3 mt-2 username">
            {{ $player->username }}
        </div>

        @if (isset($playerRank))
            <h5 class="rank pb-1 pt-2">
                Rank #{{ $playerRank }}
            </h5>
        @endif

        <div class="points font-secondary-bold {{ $pgr->won ? 'won' : 'lost' }}">
            @if ($pgr->points > 0)
                <strong class="me-1">Won {{ '+' }}</strong>
            @else
                <strong class="me-1">Lost</strong>
            @endif
            {{ $pgr->points }} points
        </div>

        <div class="faction mt-2" style="padding-left:0;">
            @if ($pgr->stats)
                @php $playerStats2 = \App\Stats2::where("id", $pgr->stats->id)->first(); @endphp
                @php $playerCountry = $playerStats2->faction($history->ladder->game, $pgr->stats->cty); @endphp
                <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
            @endif
        </div>
    </a>

    {{-- 
    <div class="player-details">
        <h2 class="username">
            <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
                {{ $player->username }}
            </a>
        </h2>
--}}

</div>


@if (isset($extraStats) && $extraStats)
    <div class="m-auto text-center mt-4">
        @if (!$history->ladder->clans_allowed)
            <div style="font-size: 1rem" class="mt-2 mb-2 font-secondary-bold">
                <?php $tier = $player->getCachedPlayerTierByLadderHistory($history); ?>
                <span class="me-2">
                    {!! \App\Helpers\LeagueHelper::getLeagueIconByTier($tier) !!}
                </span>
                {{ \App\Helpers\LeagueHelper::getLeagueNameByTier($tier) }}
            </div>
        @endif

        <div style="font-size: 0.8rem" class="mt-2 mb-2 font-secondary-bold">
            <strong>Funds Left: </strong> {{ $gameStats->crd }}
        </div>

        <div style="font-size: 0.8rem" class="mt-2 mb-2 font-secondary-bold">
            <strong>Pings: </strong> {{ $pgr->pings }}
        </div>
    </div>
@endif
