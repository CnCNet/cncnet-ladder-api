@props([
    'history',
    'game'
])
<a href="{{ '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/games/' . $game->id }}"
   class="game-box"
   data-timestamp="{{ $game->updated_at->timestamp }}">

    <div class="map-preview">
        <img src="{{ \App\Helpers\SiteHelper::getMapPreviewUrl($history, $game->qmMatch?->map?->map, $game->qmMatch?->map?->map->hash) }}" alt="" />
    </div>

    <div class="details text-center">
        <h4 class="title mb-2">{{ $game->qmMatch?->map?->description }}</h4>
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

    <div class="mt-5">


        @if ($history->ladder->ladder_type === \App\Models\Ladder::TWO_VS_TWO)
            @php
                $groupedGamePlayerResults = [];
                foreach ($game->player_game_reports as $pgr) {
                    $groupedGamePlayerResults[$pgr->player->qmPlayer->team][] = $pgr;
                }
            @endphp
            @php $vs = 0; @endphp;
            @foreach ($groupedGamePlayerResults as $team => $gamePlayerArr)
                @foreach ($gamePlayerArr as $k => $gamePlayer)
                    @if ($vs == 2)
                        <p class="vs">vs</p>
                    @endif
                    <x-ladder.game-box-partial
                            :history="$history"
                            :game-player="$gamePlayer"
                            :index="$k"
                    />
                    @php $vs++; @endphp
                @endforeach
            @endforeach
        @else
            @foreach ($game->player_game_reports as $k => $gamePlayer)
                <x-ladder.game-box-partial
                    :history="$history"
                    :game-player="$gamePlayer"
                    :index="$k"
                />
                @if ($k == 0)
                    <p class="vs">vs</p>
                @endif
            @endforeach
        @endif

    </div>
</a>
