@props(['history', 'game'])
@php
    $mapName = $game->qmMatch?->map?->description;
@endphp
<a href="{{ route('ladder.game', ['date' => $history->short, 'game' => $history->ladder->abbreviation, 'gameId' => $game->id]) }}" class="game-box"
    data-timestamp="{{ $game->updated_at->timestamp }}">

    <div class="map-preview">
        <img src="{{ \App\Helpers\SiteHelper::getMapPreviewUrl($history, $game->qmMatch?->map?->map, $game->qmMatch?->map?->map->hash) }}"
            alt="" />
    </div>

    <div class="details text-center">
        <h4 class="title mb-2">{{ $mapName }}</h4>
        <small>Played {{ $game->updated_at->diffForHumans() }}</small>

        @if ($game->report !== null)
            <div>
                <small class="status text-capitalize">
                    <strong>Duration:</strong>
                    {{ gmdate('H:i:s', $game->report->duration) }}
                </small>
            </div>
            <div>
                <small class="status text-capitalize"><strong>Average FPS:</strong> {{ $game->report->fps }}</small>
            </div>
        @endif
    </div>

    <div class="mt-5 text-center">

        @if ($history->ladder->ladder_type !== \App\Models\Ladder::ONE_VS_ONE)
            @php
                $groupedGamePlayerResults = [];
                $observerPlayers = [];
                foreach ($game->report->playerGameReports as $pgr) {
                    $t = $game?->qmMatch?->findQmPlayerByPlayerId($pgr->player_id)?->team;
                    if ($t == null || strtolower($t) == "observer") {
                        $observerPlayers[] = $pgr;
                    } elseif ($t != null) {
                        $groupedGamePlayerResults[$t][] = $pgr;
                    }
                }
            @endphp
            @php $vs = 0; @endphp
            @foreach ($groupedGamePlayerResults as $team => $gamePlayerArr)
                @foreach ($gamePlayerArr as $k => $gamePlayer)
                    @if ($vs == 2)
                        <em class="font-impact text-center">Vs</em>
                    @endif
                    <x-ladder.listing.game-box-partial :history="$history" :game-player="$gamePlayer" :index="$k" />
                    @php $vs++; @endphp
                @endforeach
            @endforeach
            @foreach ($observerPlayers as $observerPlayer)
                <span class="align-middle ms-2" title="Observer">
                    <x-ladder.listing.game-box-partial :history="$history" :game-player="$observerPlayer" :index="null" />
                    <span class="material-symbols-outlined" style="vertical-align:middle;">visibility</span>
                </span>
            @endforeach
        @else
            @foreach ($game->report->playerGameReports as $k => $gamePlayer)
                <x-ladder.listing.game-box-partial :history="$history" :game-player="$gamePlayer" :index="$k" />
                @if ($k == 0)
                    <em class="font-impact text-center">Vs</em>
                @endif
            @endforeach
        @endif

    </div>
</a>
