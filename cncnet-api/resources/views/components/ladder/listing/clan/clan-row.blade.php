@props([
    'history',
    'clanCache',
    'statsXOfTheDay',
    'ranks',
    'mostUsedFactions'
])
@php
    $username = $clanCache->clan_name;
    $url = \App\Models\URLHelper::getClanProfileLadderUrl($history, $clanCache->clan);
    $rank = $ranks[$clanCache->id] ?? 9999;
    $points = $clanCache->points;
    $wins = $clanCache->wins;
    $losses = $clanCache->games - $clanCache->wins;
    $totalGames = $clanCache->games;
    $avatar = $clanCache->getClanAvatar();
    $mostPlayedFaction = $mostUsedFactions[$clanCache->id] ?? '';

    $heart = '';
    if ($history->ladder->abbreviation == 'yr' && str_contains(strtolower($username ?? ''), 'irish')) {
        $heart = '🍀';
    }
@endphp
<div class="player-row rank-{{ $rank }}">
    <div class="player-profile d-flex d-lg-none">
        <div class="player-rank player-stat">
            #{{ $rank ?? 'Unranked' }}
        </div>
        <a class="player-avatar player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
        </a>
        <a class="player-username player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @if ($rank == 1)
                {{ $username }} <span style="color:gold;padding-left:0.5rem;"> {{ $heart }}</span>
            @else
                {{ $username }} <span style="color:red;padding-left:0.5rem;"> {{ $heart }}</span>
            @endif
        </a>
    </div>

    <div class="player-profile d-none d-lg-flex">
        <div class="player-rank player-stat">
            #{{ $rank ?? 'Unranked' }}
        </div>

        <a class="player-avatar player-stat d-none d-lg-flex" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
        </a>

        <a class="player-country player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @if ($mostPlayedFaction)
                <div class="{{ $history->ladder->game }} player-faction player-faction-{{ strtolower($mostPlayedFaction) }}"></div>
            @endif
        </a>
        <a class="player-username player-stat d-none d-lg-flex" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @if ($rank == 1)
                {{ $username }} <span style="color:gold;padding-left:0.5rem;"> {{ $heart }}</span>
            @else
                {{ $username }} <span style="color:red;padding-left:0.5rem;"> {{ $heart }}</span>
            @endif
        </a>
    </div>

    <div class="player-profile-info">
        <div class="player-points player-stat">{{ $points }} <span>points</span></div>
        <div class="player-wins player-stat">{{ $wins }} <span>won</span></div>
        <div class="player-losses player-stat">{{ $losses }} <span>lost</span></div>
        <div class="player-games player-stat">{{ $totalGames }} <span>games</span></div>
    </div>
    <a href="{{ $url }}" class="player-link">
        <i class="bi bi-chevron-right"></i>
    </a>
</div>
