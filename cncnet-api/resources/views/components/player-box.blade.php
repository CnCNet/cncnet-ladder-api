<a href="{{ $url or "/404"}}" class="player-box-link">
    <div class="player-box player-card {{ $playerCard or "carville"}}">
        <div class="details text-left">
            <div class="player-badge badge-1x">
                <img src="/images/badges/{{ $badge["badge"]. ".png"}}" style="max-width:100%">
            </div>
            <h1 class="rank">
            @if ($rank != null)
            Rank #{{ $rank or "Unranked" }}
            @endif
            </h1>
            <p class="username">{{ $username or "" }}</p>
            <p class="points">Points {{ $points or "-1" }}</p>
            <ul class="list-unstyled extra-stats">
                <li>
                    Wins <i class="fa fa-level-up fa-fw fa-lg"></i> {{ $wins }}
                </li>
                <li>
                    Games <i class="fa fa-diamond fa-fw fa-lg"></i> {{ $totalGames }}
                </li>
            </ul>
            <div class="player-box-faction hidden-xs faction faction-{{ strtolower($side) }}"></div>
        </div>
    </div>
</a>
