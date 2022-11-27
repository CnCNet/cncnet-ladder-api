@php
    $mapPreview = 'https://ladder.cncnet.org/images/maps/' . $history->ladder->abbreviation . '/' . $map->hash . '.png';
    
    // TODO: Map data needed
    $webMapWidth = 768;
    $webMapHeight = 447;
    $mapStartX = $map->mapHeaders->startX ?? -1;
    $mapStartY = $map->mapHeaders->startY ?? -1;
    $mapWidth = $map->mapHeaders->width ?? -1;
    $mapHeight = $map->mapHeaders->height ?? -1;
    $mapWaypoints = $map->mapHeaders()->mapWaypoints ?? [];
@endphp

<div class="container">
    <div class="d-flex justify-content-center">
        @include('ladders.game._map-preview', [
            'playerGameReports' => $playerGameReports,
            'mapStartX' => $mapStartX,
            'mapStartY' => $mapStartY,
            'mapPreview' => $mapPreview,
            'mapWidth' => $mapWidth,
            'mapHeight' => $mapHeight,
        ])
    </div>
</div>
