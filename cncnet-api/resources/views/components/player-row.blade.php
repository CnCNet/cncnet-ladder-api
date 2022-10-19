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

<div class="player-rank-row">
    <div class="player-rank">
        {{ $rank or 'Unranked' }}
    </div>
    <div class="player-avatar">
        @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
    </div>
    <div class="player-country">
        @if ($side)
            <div class="most-used-country country-{{ $game }}-{{ strtolower($side) }}"></div>
        @endif
    </div>
    <div class="player-username">
        {{ $username or '' }}
    </div>
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
    <div class="player-points">{{ $points }} <span>points</span></div>
    <div class="player-wins">{{ $wins }} <span>wins</span></div>
    <div class="player-games">{{ $totalGames }} <span>games</span></div>
</div>
