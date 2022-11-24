@php
    $mapPreview = 'https://ladder.cncnet.org/images/maps/' . $history->ladder->abbreviation . '/' . $map->hash . '.png';
    
    // TODO: Map data needed
    $webMapWidth = 768;
    $webMapHeight = 447;
    $mapStartX = 209;
    $mapStartY = 54;
    $mapWidth = 93;
    $mapHeight = 109;
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
            'webMapWidth' => $webMapWidth,
            'webMapHeight' => $webMapHeight,
        ])
    </div>
</div>
