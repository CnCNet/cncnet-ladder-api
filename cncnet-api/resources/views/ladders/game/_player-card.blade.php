<div class="player-card">
    <div class="player-avatar" style="width:120px;height:120px;">
        <a href="{{ \App\Models\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">

            <div class="avatar-with-faction">
                @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 120])

                <div class="faction mt-2" style="padding-left:0;">
                    @if ($pgr->stats)
                        @php $playerStats2 = $pgr->stats; @endphp
                        @php $playerCountry = $playerStats2->faction($history->ladder, $pgr->stats->cty); @endphp
                        <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
                    @endif
                </div>
            </div>
        </a>
    </div>

    <a href="{{ \App\Models\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
        <h4 class="mt-2 mb-0 username">
            {{ $player->username }}

            @if ($player->user->getEmoji())
                @include('components.emoji', ['emoji' => $player->user->getEmoji()])
            @endif
        </h4>

        <div class="pt-2 pb-2 points font-secondary-bold {{ $pgr->points > 0 ? 'won' : 'lost' }}">
            @if ($pgr->points > 0)
                Won +{{ $pgr->points }} points
            @else
                <strong class="me-1">Lost {{ $pgr->points }} points</strong>
            @endif
        </div>

        @if (isset($playerRank))
            <h6 class="rank">
                Rank #{{ $playerRank }}
            </h6>
        @endif

        @if (isset($disablePointFilter) && $disablePointFilter != null)
            <h6 class="rank">
                disabledPointFilter: {{ $disablePointFilter }}
            </h6>
        @endif
    </a>
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
