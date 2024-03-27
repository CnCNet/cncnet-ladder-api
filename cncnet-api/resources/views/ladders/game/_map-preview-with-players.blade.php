@php
    try {
        $mapPreview = \App\Helpers\SiteHelper::getMapPreviewUrl($history, $map, $gameReport->game);
        $mapPreviewSize = getimagesize($mapPreview);
    
        $webMapWidth = $mapPreviewSize[0];
        $webMapHeight = $mapPreviewSize[1];
    
        $mapStartX = $map->mapHeaders->startX ?? -1;
        $mapStartY = $map->mapHeaders->startY ?? -1;
        $mapWidth = $map->mapHeaders->width ?? -1;
        $mapHeight = $map->mapHeaders->height ?? -1;
        $ratioX = $webMapWidth / $mapWidth;
        $ratioY = $webMapHeight / $mapHeight;
    
        $hasMapData = true;
    } catch (\Exception $ex) {
        $hasMapData = false;
    }
@endphp

<div class="container">
    <div class="d-flex justify-content-center">
        @if ($hasMapData)
            {{-- <div class="map-preview d-flex d-lg-none">
                <img src="{{ $mapPreview }}" style="max-width:100%" />
            </div> --}}

            <div class="map-preview d-lg-flex" style="background-image:url('{{ $mapPreview }}'); "
                 data-map-width="{{ $webMapWidth }}"
                 data-map-height="{{ $webMapHeight }}">
                @foreach ($playerGameReports as $k => $pgr)
                    @php

                        $pointReport = $pgr;
                        if ($history->ladder->clans_allowed) {
                            $pointReport = $pgr->gameReport->getPointReportByClan($pgr->clan_id);
                        }
                        
                        $hasValidSpawnData = false;
                        $gameStats = $pgr->stats;
                        $player = $pgr->player()->first();
                        
                        try {
                            $clan = $pgr->clan;
                        } catch (Exception $ex) {
                        }
                        
                        # Player positions plotted onto map preview
                        $playerX = 0;
                        $playerY = 0;
                        
                        $playerSpawnPosition = isset($pgr->spawn) ? $pgr->spawn + 1 : -1;
                        
                        if ($playerSpawnPosition !== -1) {
                            if (isset($map->mapHeaders)) {
                                $position = $map->mapHeaders->waypoints->where('bit_idx', $playerSpawnPosition)->first();
                        
                                if ($position) {
                                    $playerX = $ratioX * ($position->x - $mapStartX);
                                    $playerY = $ratioY * ($position->y - $mapStartY);
                        
                                    $hasValidSpawnData = true;
                                }
                            }
                        }
                    @endphp

                    @if ($hasValidSpawnData)
                        <div id="marker-{{ $k }}" class="player-marker"
                             style="left: {{ $playerX }}px; top: {{ $playerY }}px;"
                             data-x="{{ $playerX }}" data-y="{{ $playerY }}">
                            <div class="player-start-position {{ $gameStats->colour($gameStats->col) }}">
                                {{ $playerSpawnPosition != -1 ? $playerSpawnPosition : 'No spawn data' }}
                            </div>
                        </div>

                        <div id="playerdetails-{{ $k }}"
                             class="player player-{{ $gameStats->colour($gameStats->col) }} player-details"
                             style="display:none;">

                            <div class="player-avatar">
                                @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 35])
                            </div>

                            <div class="player-details">
                                <div class="username">
                                    {{ $player->username }}

                                    @if ($clan)
                                        {{ $clan->short }}
                                    @endif
                                </div>

                                <div class="status text-uppercase status-{{ $pointReport->won ? 'won' : 'lost' }}">
                                    @if ($pointReport->won)
                                        Won
                                    @elseif($pointReport->draw)
                                        Draw
                                    @elseif($pointReport->disconnected)
                                        Disconnected
                                    @else
                                        Lost
                                    @endif
                                </div>

                                <div class="faction">
                                    @if ($pgr->stats)
                                        @php $playerStats2 = \App\Models\Stats2::where("id", $pgr->stats->id)->first(); @endphp
                                        @php $playerCountry = $playerStats2->faction($history->ladder->game, $pgr->stats->cty); @endphp
                                        <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="map-preview d-lg-none">
                <img src="{{ $mapPreview }}" style="max-width:100%"/>
            </div>
        @endif
    </div>
</div>

@section('js')
    <script src="/js/popper.js"></script>
    <script>
        ((function () {

            let marker1 = document.querySelector('#marker-0');
            let player1 = document.querySelector('#playerdetails-0');

            Popper.createPopper(marker1, player1, {
                modifiers: [{
                    name: 'offset',
                    options: {
                        offset: [5, 5],
                    },
                },],
            });

            let marker2 = document.querySelector('#marker-1');
            let player2 = document.querySelector('#playerdetails-1');

            Popper.createPopper(marker2, player2, {
                modifiers: [{
                    name: 'offset',
                    options: {
                        offset: [5, 5],
                    },
                },],
            });

            let marker3 = document.querySelector('#marker-2');
            let player3 = document.querySelector('#playerdetails-2');

            Popper.createPopper(marker3, player3, {
                modifiers: [{
                    name: 'offset',
                    options: {
                        offset: [5, 5],
                    },
                },],
            });

            let marker4 = document.querySelector('#marker-3');
            let player4 = document.querySelector('#playerdetails-3');

            Popper.createPopper(marker4, player4, {
                modifiers: [{
                    name: 'offset',
                    options: {
                        offset: [5, 5],
                    },
                },],
            });

            player1 ? player1.style.display = "" : "";
            player2 ? player2.style.display = "" : "";
            player3 ? player3.style.display = "" : "";
            player4 ? player4.style.display = "" : "";

        })());

        ((function () {
            // Function to update the positions of the player markers and scale the map container
            function updateMapPreview() {
                let mapPreview = document.querySelector(".map-preview");
                let mapWidth = mapPreview.dataset.mapWidth;
                let mapHeight = mapPreview.dataset.mapHeight;

                mapPreview.style.width = mapWidth + "px";
                mapPreview.style.height = mapHeight + "px";

                // Get the current dimensions of the map container
                let containerWidth = mapPreview.offsetWidth;
                let containerHeight = mapPreview.offsetHeight;

                // Check if the container size has changed
                // Calculate the aspect ratio of the map image
                let mapAspectRatio = mapWidth / mapHeight;

                // Calculate the new dimensions of the map container to maintain the aspect ratio
                let newWidth, newHeight;

                if (containerWidth / containerHeight > mapAspectRatio) {
                    newHeight = containerHeight;
                    newWidth = containerHeight * mapAspectRatio;
                } else {
                    newWidth = containerWidth;
                    newHeight = containerWidth / mapAspectRatio;
                }

                // Calculate the scaling factor for player markers based on the updated dimensions
                let scaleX = newWidth / mapWidth;
                let scaleY = newHeight / mapHeight;

                // Update the size of the map container
                mapPreview.style.width = newWidth + "px";
                mapPreview.style.height = newHeight + "px";

                // Loop through each player marker and update its position
                let playerMarkers = document.querySelectorAll(".player-marker");
                playerMarkers.forEach((marker) => {
                    let playerX = parseFloat(marker.dataset
                        .x); // Assuming you're storing the original player X position as a data attribute
                    let playerY = parseFloat(marker.dataset
                        .y); // Assuming you're storing the original player Y position as a data attribute

                    // Calculate the new position based on the updated scaling factors and the current container dimensions
                    let newX = playerX * scaleX;
                    let newY = playerY * scaleY;

                    // Update the marker's position
                    marker.style.left = newX + "px";
                    marker.style.top = newY + "px";
                });
            }

            // Store the original map size before resizing
            let mapPreview = document.querySelector(".map-preview");
            let originalMapWidth = mapPreview.offsetWidth;
            let originalMapHeight = mapPreview.offsetHeight;

            // Call the function initially to set the positions and scale the map container
            updateMapPreview();

            // Add an event listener to handle window resize
            window.addEventListener("resize", () => {
                // Restore the original map size if the window is resized back to larger widths
                if (window.innerWidth >= originalMapWidth) {
                    mapPreview.style.width = originalMapWidth + "px";
                    mapPreview.style.height = originalMapHeight + "px";
                }

                // Call the function to update the positions and scale the map container
                updateMapPreview();
            });
        })());
    </script>
@endsection
