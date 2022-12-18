@php
    $mapPreview = '';
    try {
        $mapPreview = url('/images/maps/' . $history->ladder->abbreviation . '/' . $map->hash . '.png');
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
        @else
            <div class="map-preview d-lg-none">
                <img src="{{ $mapPreview }}" style="max-width:100%" />
            </div>
        @endif
    </div>
</div>
