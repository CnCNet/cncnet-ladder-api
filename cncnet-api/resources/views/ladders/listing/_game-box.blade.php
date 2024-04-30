<a href="{{ $url ?? '' }}" class="game-box" data-timestamp="{{ $date->timestamp }}">
    <div class="map-preview">
        <img src="{{ $mapPreview }}" alt="" />
    </div>

    <div class="details text-center">
        <h4 class="title mb-2">{{ $title }}</h4>
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

        @php
            if ($ladderType === \App\Models\Ladder::CLAN_MATCH) {
                $gamePlayerResults = $playerGameReports->groupBy('clan_id')->get();
            } else {
                $gamePlayerResults = $playerGameReports->get();

                $groupedGamePlayerResults = [];

                if ($ladderType === \App\Models\Ladder::TWO_VS_TWO) {
                    foreach ($gamePlayerResults as $pgr) {
                        $groupedGamePlayerResults[$pgr->player->qmPlayer->team][] = $pgr;
                    }
                }
            }
        @endphp

        @if ($ladderType === \App\Models\Ladder::TWO_VS_TWO)
            @php $vs = 0; @endphp;
            @foreach ($groupedGamePlayerResults as $team => $gamePlayerArr)
                @foreach ($gamePlayerArr as $k => $gamePlayer)
                    @if ($vs == 2)
                        <p class="vs">vs</p>
                    @endif
                    @include('ladders.listing._game-box-partial')
                    Debug: Team {{ $team }}
                    @php $vs++; @endphp
                @endforeach
            @endforeach
        @else
            @foreach ($gamePlayerResults as $k => $gamePlayer)
                @include('ladders.listing._game-box-partial')
                @if ($k == 0)
                    <p class="vs">vs</p>
                @endif
            @endforeach
        @endif

    </div>
</a>
