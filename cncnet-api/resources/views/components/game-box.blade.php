<div class="game-box">
    <div class="preview" style="background-image:url(/images/maps/{{ $game }}/{{ $map or '' }}.png)">
        <a href="{{ $url or '' }}" class="status status-{{ $status }}">
            <span class="material-symbols-outlined">
                swords
            </span>
        </a>
    </div>

    <a href="{{ $url or '' }}" class="game-box-link" data-toggle="tooltip" data-placement="top" title="View game" data-timestamp="{{ $date->timestamp }}">
        <div class="details text-center">
            <h4 class="title">{{ $title }}</h4>
            <small class="status text-capitalize">{{ $status . ' ' . $date->diffForHumans() }}</small>

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

        <div class="footer text-center {{ $history->ladder->abbreviation }}">
            <?php $gamePlayerResults = $gamePlayers->get(); ?>
            @foreach ($gamePlayerResults as $k => $gamePlayer)
                <div class="player {{ $gamePlayer->won == true ? 'won' : 'lost' }} player-order-{{ $k }}">

                    @if ($gamePlayer->stats)
                        @php $playerStats2 = \App\Stats2::where("id", $gamePlayer->stats->id)->first(); @endphp
                        @php $playerCountry = $playerStats2->faction($history->ladder->game, $gamePlayer->stats->cty); @endphp
                        <div class="player-faction player-faction-{{ $playerCountry }}"></div>
                    @endif

                    <h5>
                        {{ $gamePlayer->player->username }}
                        <span class="points">
                            @if ($gamePlayer->points >= 0)
                                +
                            @endif{{ $gamePlayer->points }}
                        </span>
                    </h5>
                </div>

                @if ($k == 0)
                    <p class="vs">vs</p>
                @endif
            @endforeach
        </div>
    </a>
</div>
