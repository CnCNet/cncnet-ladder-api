@php
    $ratioX = $webMapWidth / $mapWidth;
    $ratioY = $webMapHeight / $mapHeight;
    
    // TODO: Waypoint data
    $webWayPoint1_X = $ratioX * (285 - $mapStartX);
    $webWayPoint1_Y = $ratioY * (76 - $mapStartY);
    
    $webWayPoint2_X = $ratioX * (216 - $mapStartX);
    $webWayPoint2_Y = $ratioY * (131 - $mapStartY);
@endphp

<div class="map-preview" style="background-image:url('{{ $mapPreview }}'); width: {{ $webMapWidth }}px; height: {{ $webMapHeight }}px">
    @foreach ($playerGameReports as $k => $pgr)
        @php $gameStats = $pgr->stats; @endphp
        @php $player = $pgr->player()->first(); @endphp

        {{-- TODO waypoints by player --}}
        @php $webWayPointX = $k == 0 ?  $webWayPoint1_X: $webWayPoint2_X; @endphp
        @php $webWayPointY = $k == 0 ?  $webWayPoint1_Y: $webWayPoint2_Y; @endphp

        <div class="player player-{{ $gameStats->colour($gameStats->col) }}" style="left: {{ $webWayPointX }}px; top: {{ $webWayPointY }}px;">

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
