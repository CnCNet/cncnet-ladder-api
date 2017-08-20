<a href="{{ $url or "/404"}}" class="player-box-link">
    <div class="player-box player-card {{ $playerCard or "carville"}}">
        <div class="details text-left">
            <div class="player-badge {{ $badge or "" }}"></div>
            <h1 class="rank">Rank #{{ $rank or "Unranked" }}</h1>
            <p class="username">{{ $username or "" }}</p>
            <p class="points">{{ $points or "-1" }}</p>
        </div>
    </div>
</a>