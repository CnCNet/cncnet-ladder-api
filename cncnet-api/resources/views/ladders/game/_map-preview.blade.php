@php
    $ratioX = $webMapWidth / $mapWidth;
    $ratioY = $webMapHeight / $mapHeight;
    
    // TODO: Waypoint data
    $webWayPoint1_X = $ratioX * (285 - $mapStartX);
    $webWayPoint1_Y = $ratioY * (76 - $mapStartY);
    
    $webWayPoint2_X = $ratioX * (216 - $mapStartX);
    $webWayPoint2_Y = $ratioY * (131 - $mapStartY);
@endphp

<div class="map-preview d-lg-none">
    <img src="{{ $mapPreview }}" style="max-width:100%" />
</div>
<div class="map-preview d-none d-lg-flex" style="background-image:url('{{ $mapPreview }}'); width: {{ $webMapWidth }}px; height: {{ $webMapHeight }}px">
    @foreach ($playerGameReports as $k => $pgr)
        @php $gameStats = $pgr->stats; @endphp
        @php $player = $pgr->player()->first(); @endphp

        @php
            $playerSpawnPosition = isset($pgr->spawn) ? $pgr->spawn : -1;
            if ($playerSpawnPosition == -1) {
                $x = -1;
                $y = -1;
            } else {
                $waypoints = $map->mapHeaders->waypoints->where('bit_idx', $playerSpawnPosition)->first();
                $x = $ratioX * ($position->x - $mapStartX);
                $y = $ratioY * ($position->y - $mapStartY);
            }
        @endphp

        @if ($playerSpawnPosition == -1)
            <div style="display: none" class="no-spawn">
            @else
                <div class="player player-{{ $gameStats->colour($gameStats->col) }}" style="left: {{ $x }}px; top: {{ $y }}px;">
        @endif
        <div class="player-avatar">
            @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 35])
        </div>

        <div class="player-details">
            <div class="username">
                {{ $player->username }}
            </div>
            <div class="status text-uppercase status-{{ $pgr->won ? 'won' : 'lost' }}">
                @if ($pgr->won)
                    Won
                @elseif($pgr->draw)
                    Draw
                @elseif($pgr->disconnected)
                    Disconnected
                @else
                    Lost
                @endif
            </div>
        </div>
</div>
@endforeach
</div>
