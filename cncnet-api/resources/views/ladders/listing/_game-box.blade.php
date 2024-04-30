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
        <?php
        $gamePlayerResults = $gamePlayers->get();
        if ($isClanGame) {
            $gamePlayerResults = $gamePlayers->groupBy('clan_id')->get();
        } else {
            $gamePlayerResults = $gamePlayers->get();
        }
        ?>

        @foreach ($gamePlayerResults as $k => $gamePlayer)
            <div class="player {{ $gamePlayer->won == true ? 'won' : 'lost' }} player-order-{{ $k }}">

                <div class="player-name-faction">
                    @if ($gamePlayer->stats)
                        @php $playerStats2 = \App\Models\Stats2::where("id", $gamePlayer->stats->id)->first(); @endphp
                        @php $playerCountry = $playerStats2->faction($history->ladder->game, $gamePlayer->stats->cty); @endphp

                        <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
                    @endif

                    @if ($isClanGame)
                        <span class="ps-3 pe-1">
                            <i class="bi bi-flag-fill icon-clan"></i>
                            @if ($gamePlayer->clan)
                                {{ $gamePlayer->clan->short }}
                            @endif
                        </span>
                    @else
                        <span class="ps-3 pe-1">
                            {{ $gamePlayer->player->username }}
                        </span>
                    @endif

                    <span class="points">
                        {{ $gamePlayer->points >= 0 ? "+$gamePlayer->points" : $gamePlayer->points }}
                    </span>
                </div>
            </div>

            @if ($k == 0)
                <p class="vs">vs</p>
            @endif
        @endforeach
    </div>
</a>
