@if ($playerGameReport->stats)
    @php $playerStats2 = \App\Stats2::where("id", $playerGameReport->stats->id)->first(); @endphp
    @php $clanWon = $playerGameReport->gameReport->checkIsWinningClan($playerGameReport->clan_id); @endphp
@endif

<div class="clan-header order-{{ $order }}">
    <div class="clan-avatar">
        <a href="{{ $clanProfileUrl }}" title="View {{ $clanName }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 150, 'type' => 'clan'])
        </a>
    </div>

    <div class="clan-rank">
        <h1 class="clan">
            <a href="{{ $clanProfileUrl }}" title="View {{ $clanName }}'s clan">
                {{ $clanName }}
            </a>
        </h1>
        <h3 class="rank {{ $clanWon ? 'highlight' : 'lost' }} text-uppercase mt-0">
            {{ $clanWon ? 'Won' : 'Lost' }}
            {{ $playerGameReport->points >= 0 ? "+$playerGameReport->points" : $playerGameReport->points }}
        </h3>
    </div>
</div>
