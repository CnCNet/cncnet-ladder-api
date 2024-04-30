<div class="player {{ $gamePlayer->won == true ? 'won' : 'lost' }} player-order-{{ $k }}">

    <div class="player-name-faction">
        @if ($gamePlayer->stats)
            @php $playerStats2 = \App\Models\Stats2::where("id", $gamePlayer->stats->id)->first(); @endphp
            @php $playerCountry = $playerStats2->faction($history->ladder->game, $gamePlayer->stats->cty); @endphp

            <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
        @endif

        @if ($isClanGame)
            <span class="ps-3 pe-1">
                <i class="bi bi-flag-fill icon-clan"></i>
                @if ($gamePlayer->clan)
                    {{ $gamePlayer->clan->short }}
                @endif
            </span>
        @else
            <span class="ps-3 pe-1">
                {{ $gamePlayer->player->username }}
            </span>
        @endif

        <span class="points">
            {{ $gamePlayer->points >= 0 ? "+$gamePlayer->points" : $gamePlayer->points }}
        </span>
    </div>
</div>
