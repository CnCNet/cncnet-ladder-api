<a href="{{ $url }}" class="player-box-link">
    <div class="player-box player-card {{ $playerCard['short'] ?? 'carville' }}">
        <div class="details text-left">
            <div class="player-badge badge-1x">
                <img src="/images/badges/{{ $badge['badge'] . '.png' }}" style="max-width:100%">
            </div>
            <h1 class="rank">
                @if ($rank != null)
                    Rank #{{ $rank ?? 'Unranked' }}
                @endif
            </h1>

            <p class="username">{{ $username }}</p>
            <p class="points">Points {{ $points ?? '-1' }}</p>
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
</a>
