@php
    $groupedPlayerGameReports = [];
    if ($ladderType == \App\Models\Ladder::TWO_VS_TWO) {
        foreach ($playerGameReports as $playerGameReport) {
            $team = $playerGameReport->game->qmMatch->findQmPlayerByPlayerId($playerGameReport->player_id)->team;
            $groupedPlayerGameReports[$team][] = $playerGameReport;
        }
    }
@endphp

<a href="{{ $url ?? '' }}" class="game-box" data-timestamp="{{ $date->timestamp }}">
    <div class="map-preview">
        <img src="{{ $mapPreview }}" alt="" />
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

    <div class="mt-5 text-center">
        @if ($ladderType === \App\Models\Ladder::TWO_VS_TWO)
            @php $vs = 0; @endphp
            @foreach ($groupedPlayerGameReports as $team => $teamPlayerGameReportArr)
                @foreach ($teamPlayerGameReportArr as $k => $pgr)
                    @if ($vs == 2)
                        <em class=" font-impact text-center">Vs</em>
                    @endif

                    @include('ladders.listing._game-box-partial', ['pgr' => $pgr])
                    @php $vs++; @endphp
                @endforeach
            @endforeach
        @else
            @foreach ($playerGameReports as $k => $pgr)
                @include('ladders.listing._game-box-partial', ['pgr' => $pgr])
                @if ($k == 0)
                    <p class="vs">vs</p>
                @endif
            @endforeach
        @endif

    </div>
</a>
