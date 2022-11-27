@php
    $mapPreview = 'https://ladder.cncnet.org/images/maps/' . $history->ladder->abbreviation . '/' . $map->hash . '.png';
    
    $mapPreviewSize = getimagesize($mapPreview);
    $webMapWidth = $mapPreviewSize[0];
    $webMapHeight = $mapPreviewSize[1];
    
    $mapStartX = $map->mapHeaders->startX ?? -1;
    $mapStartY = $map->mapHeaders->startY ?? -1;
    $mapWidth = $map->mapHeaders->width ?? -1;
    $mapHeight = $map->mapHeaders->height ?? -1;
    $ratioX = $webMapWidth / $mapWidth;
    $ratioY = $webMapHeight / $mapHeight;
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
            'ratioX' => $ratioX,
            'ratioY' => $ratioY,
        ])
    </div>
</div>
