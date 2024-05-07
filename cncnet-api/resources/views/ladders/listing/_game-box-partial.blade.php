<div class="player {{ $pgr->won == true ? 'won' : 'lost' }} player-order-{{ $k }}">

    <div class="player-name-faction">
        @if ($pgr->stats)
            @php $playerStats2 = \App\Models\Stats2::where("id", $pgr->stats->id)->first(); @endphp
            @php $playerCountry = $playerStats2->faction($history->ladder->game, $pgr->stats->cty); @endphp

            <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
        @endif

        @if ($isClanGame)
            <span class="ps-3 pe-1">
                <i class="bi bi-flag-fill icon-clan"></i>
                @if ($pgr->clan)
                    {{ $pgr->clan->short }}
                @endif
            </span>
        @else
            <span class="ps-3 pe-1">
                {{ $pgr->player->username }}
            </span>
        @endif

        <span class="points">
            {{ $pgr->points >= 0 ? "+$pgr->points" : $pgr->points }}
        </span>
    </div>
</div>
