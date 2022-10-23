{{-- <a href="{{ $url or '/404' }}" class="player-box-link">
    <div class="player-box player-card {{ $playerCard or 'carville' }}">
        <div class="details text-left">
            <div class="player-badge badge-1x">
                <img src="/images/badges/{{ $badge['badge'] . '.png' }}" style="max-width:100%">
            </div>
            <h1 class="rank">
                @if ($rank != null)
                    Rank #{{ $rank or 'Unranked' }}
                @endif
            </h1>
            <p class="username">{{ $username or '' }}</p>
            <p class="points">Points {{ $points or '-1' }}</p>
            <ul class="list-unstyled extra-stats">
                <li>
                    Wins <i class="fa fa-level-up fa-fw fa-lg"></i> {{ $wins }}
                </li>
                <li>
                    Games <i class="fa fa-diamond fa-fw fa-lg"></i> {{ $totalGames }}
                </li>
            </ul>
            @if ($side)
                <div class="most-used-country country-{{ $game }}-{{ strtolower($side) }}"></div>
            @endif
        </div>
    </div>
</a> --}}


@php
    $countryName = '';
    $side = null;
    
    if ($game == 'yr') {
        $side = \App\Side::where('local_id', $player->country)
            ->where('ladder_id', $history->ladder->id)
            ->first();
    } else {
        if ($player->side !== null) {
            if (array_key_exists($player->side, $sides)) {
                $countryName = $sides[$player->side];
            }
        }
    }
    
    if ($side !== null) {
        $countryName = $side->name;
    }
@endphp

<div class="player-row">
    <div class="player-profile visible-xs">
        <div class="player-rank player-stat">
            #{{ $rank or 'Unranked' }}
        </div>
        <a class="player-avatar player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
        </a>
        <a class="player-username player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            {{ $username or '' }}
        </a>
    </div>

    <div class="player-profile hidden-xs">
        <div class="player-rank player-stat">
            #{{ $rank or 'Unranked' }}
        </div>

        <a class="player-avatar player-stat hidden-xs" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
        </a>

        <a class="player-country player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @if ($countryName)
                <div class="most-used-country country-{{ $game }}-{{ strtolower($countryName) }}"></div>
            @endif
        </a>
        <a class="player-username player-stat hidden-xs" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            {{ $username or '' }}
        </a>
    </div>

    <div class="player-profile-info">
        <div class="player-social">
            @if ($twitch)
                <a href=" {{ $twitch }}"><i class="fa fa-twitch"></i></a>
            @endif
            @if ($youtube)
                <a href=" {{ $youtube }}"><i class="fa fa-youtube"></i></a>
            @endif
            {{-- @if ($discord)
            <a href=" {{ $discord }}"><i class="fa fa-discord"></i></a>
            @endif --}}
        </div>
        <div class="player-points player-stat">{{ $points }} <span>points</span></div>
        <div class="player-wins player-stat">{{ $wins }} <span>wins</span></div>
        <div class="player-games player-stat">{{ $totalGames }} <span>games</span></div>
    </div>
</div>
