<div class="event-result">
    <p class="fs-4 fw-bold mb-0">🏅 {{ $result['name'] }}</p>
    @if (!empty($result['note']))
        <p class="small mb-0">{{ $result['note'] }}</p>
    @endif
    @if (!empty($result['game_url']))
        <a class="small" href="{{ $result['game_url'] }}">View game</a>
    @endif
</div>
