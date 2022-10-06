<div class="game-box">
    <div class="preview" style="background-image:url(/images/maps/{{ $game }}/{{ $map or '' }}.png)">
        <a href="{{ $url or '' }}" class="status status-{{ $status }}"></a>
    </div>

    <a href="{{ $url or '' }}" class="game-box-link" data-toggle="tooltip" data-placement="top" title="View game">
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

        <div class="footer text-center">
            <?php $gamePlayerResults = $gamePlayers->get(); ?>
            @foreach($gamePlayerResults as $k => $gamePlayer)

                <div class="player {{ $gamePlayer->won == true ? 'won': 'lost' }} player-order-{{ $k }}">

                    @php $playerFaction = \App\Stats2::getCountryById($gamePlayer->stats->cty); @endphp
                    <div class="player-faction player-faction-{{ $playerFaction }}"></div>

                    <h5>
                        {{ $gamePlayer->player->username }} 
                        <span class="points">
                        @if ($gamePlayer->points >= 0)+@endif 
                        {{ $gamePlayer->points }}
                        </span>
                    </h5>
                </div>

                @if($k == 0)
                <p class="vs">vs</p>
                @endif
            @endforeach
        </div>
    </a>
</div>
