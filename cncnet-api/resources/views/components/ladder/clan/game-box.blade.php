@props([
    'history',
    'game'
])
@php
    $url = '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/games/' . $game->id;
    $status = $game->player_game_reports->first()->won ? 'won' : 'lost';
@endphp
<div class="game-box">

    <div class="preview" style="background-image:url({{ App\Helpers\SiteHelper::getMapPreviewUrl(
                                $history,
                                $game->qmMatch?->map?->map,
                                $game->qmMatch?->map?->map->hash) }})">
        <a href="{{ $url ?? '' }}" class="status">
            <i class="bi bi-flag-fill icon-clan"></i>
        </a>
    </div>

    <a href="{{ $url ?? '' }}" class="game-box-link" data-toggle="tooltip" data-placement="top"
       data-timestamp="{{ $game->updated_at->timestamp }}"
       title="View game">
        <div class="details text-center">
            <h4 class="title">{{ $game->qmMatch?->map?->description }}</h4>
            <small class="status text-capitalize">{{ $status . ' ' . $game->updated_at->diffForHumans() }}</small>

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

        <div class="footer text-center {{ $history->ladder->abbreviation }}">
            @foreach ($game->player_game_reports->groupBy('clan_id') as $k => $clanReport)
                @php
                    $clanPointReport = $clanReport->gameReport->getPointReportByClan($clanReport->clan_id);
                @endphp

                @if ($clanPointReport)
                    <div class="player {{ $clanPointReport->won == true ? 'won' : 'lost' }} player-order-{{ $k }}">

                        @if ($clanReport->stats)
                            @php $playerStats2 = \App\Models\Stats2::where("id", $clanReport->stats->id)->first(); @endphp
                            @php $playerCountry = $playerStats2->faction($history->ladder, $clanReport->stats->cty); @endphp
                            <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
                        @endif

                        <h5>
                            <span class="ps-3 pe-1">
                                <i class="bi bi-flag-fill icon-clan"></i>
                                @if ($clanReport->clan)
                                    {{ $clanReport->clan->short }}
                                @endif
                            </span>

                            <span class="points">
                                {{ $clanPointReport->points >= 0 ? "+$clanPointReport->points" : $clanPointReport->points }}
                            </span>
                        </h5>
                    </div>
                @endif

                @if ($k == 0)
                    <p class="vs">vs</p>
                @endif
            @endforeach
        </div>
    </a>
</div>
