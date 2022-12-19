<h4>Player map stats</h4>

<div class="map-stats grid">
    @foreach ($playerWinLossByMaps as $mapName => $v)
        <div class="map-row-container">
            <div class="map-name">
                <h5>{{ $mapName }}</h5>
            </div>
            <div class="map-row">
                <div class="map-preview" style="background-image:url(https://ladder.cncnet.org/images/maps/{{ $history->ladder->abbreviation }}/{{ $v['preview'] }}.png)"></div>
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
