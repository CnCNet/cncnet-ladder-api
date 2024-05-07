<a href="{{ $url ?? '' }}" class="game-box" data-timestamp="{{ $date->timestamp }}">

    <div class="map-preview">
        <img src="{{ $mapPreview }}" alt="{{ $mapName }}" />
    </div>

    <div class="details text-center">
        <h4 class="title mb-2">{{ $mapName }}</h4>
        <small>Played {{ $date->diffForHumans() }}</small>

        @if ($gameReport !== null)
            <div>
                <small class="status text-capitalize">
                    <strong>Duration:</strong>
                    {{ gmdate('H:i:s', $gameReport->duration) }}
                </small>
            </div>
            <div>
                <small class="status text-capitalize"><strong>Average FPS:</strong> {{ $gameReport->fps }}</small>
            </div>
        @endif
    </div>

    <div class="mt-5">
        @foreach ($clanGameReports as $k => $pgr)
            @include('ladders.listing._game-box-partial', ['pgr' => $pgr])
            @if ($k == 0)
                <p class="vs">vs</p>
            @endif
        @endforeach
    </div>
</a>
