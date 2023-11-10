<h4>Player map stats</h4>

<div class="map-stats grid">
    @foreach ($playerWinLossByMaps as $mapName => $v)
        <div class="map-row-container">
            <div class="map-name">
                <h6>{{ $mapName }}</h6>
            </div>
            <div class="map-row">
                @php
                    $mapPreview = \App\Helpers\SiteHelper::getMapPreviewUrl($history, $v['map'], "");
                @endphp

                <div class="map-preview" style="background-image:url({{ $mapPreview }})"></div>
                <div class="counts">
                    <div class="count won">
                        x{{ $v['won'] }} wins
                    </div>
                    <div class="count lost">
                        x{{ $v['lost'] }} losses
                    </div>
                    <div class="count total">
                        x{{ $v['total'] }} total
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
