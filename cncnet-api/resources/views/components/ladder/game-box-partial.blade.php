@props([
    'index',
    'gamePlayer',
    'history'
])
<div class="player {{ $gamePlayer->won == true ? 'won' : 'lost' }} player-order-{{ $index }}">

    <div class="player-name-faction">
        @if ($gamePlayer->stats)
            @php $playerCountry = $gamePlayer->stats->faction($history->ladder); @endphp
            <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
        @endif

        @if ($history->ladder->clans_allowed)
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
