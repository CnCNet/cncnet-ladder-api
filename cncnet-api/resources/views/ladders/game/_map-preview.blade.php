<div class="map-preview d-lg-none">
    <img src="{{ $mapPreview }}" style="max-width:100%" />
</div>

<div class="map-preview d-none d-lg-flex" style="background-image:url('{{ $mapPreview }}'); width: {{ $webMapWidth }}px; height: {{ $webMapHeight }}px">
    @foreach ($playerGameReports as $k => $pgr)
        @php
            $hasValidSpawnData = false;
            $gameStats = $pgr->stats;
            $player = $pgr->player()->first();
            
            # Player positions plotted onto map preview
            $playerX = 0;
            $playerY = 0;
            $playerSpawnPosition = isset($pgr->spawn) ? $pgr->spawn : -1;
            
            if ($playerSpawnPosition !== -1) {
                $position = $map->mapHeaders->waypoints->where('bit_idx', $playerSpawnPosition)->first();
                if ($position) {
                    $playerX = $ratioX * ($position->x - $mapStartX);
                    $playerY = $ratioY * ($position->y - $mapStartY);
                    $hasValidSpawnData = true;
                }
            }
        @endphp

        @if ($hasValidSpawnData)
            <div class="player player-{{ $gameStats->colour($gameStats->col) }}" style="left: {{ $playerX }}px; top: {{ $playerY }}px;">
                <div class="player-avatar">
                    @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 35])
                </div>

                <div class="player-details">
                    <div class="username">
                        {{ $player->username }}
                    </div>
                    <div class="player-start-position">{{ $playerSpawnPosition != -1 ? $playerSpawnPosition : 'No spawn data' }}</div>
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
        @endif
    @endforeach
</div>
