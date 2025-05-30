<div class="player-container">

    <div class="player-row">
        <div class="ms-3 me-3">
            <a href="{{ $profileUrl }}" title="View {{ $clanName }}'s profile">
                @include('components.avatar', ['avatar' => $avatar, 'size' => 55])
            </a>
        </div>
        <div class="player-username">
            <p class="fw-bold mb-1">
                <a href="{{ $clanProfileUrl }}" title="View clan {{ $clanName }}'s profile">{{ $clanName }}</a>
            </p>
        </div>
    </div>

    @if ($playerGameReport->stats)
        <div class="player-points d-flex">
            @php $playerStats2 = \App\Models\Stats2::where("id", $playerGameReport->stats->id)->first(); @endphp
            @php $playerCountry = $playerStats2->faction($history->ladder, $playerGameReport->stats->cty); @endphp

            <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>

            @php $clanWon = $playerGameReport->gameReport->checkIsWinningClan($playerGameReport->clan_id); @endphp

            <div class="game-status status-{{ $clanWon ? 'won' : 'lost' }}">
                 <span class="status-text">
                     {{ $clanWon ? 'Won' : 'Lost' }}
                 </span>

                <span class="points">
                     {{ $playerGameReport->points >= 0 ? "+$playerGameReport->points" : $playerGameReport->points }}
                 </span>
            </div>
        </div>
    @endif
</div>
