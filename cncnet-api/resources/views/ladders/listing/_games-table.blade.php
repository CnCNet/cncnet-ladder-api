<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>

            @foreach ($games as $game)

                @php
                    $playerGameReports = \App\Models\PlayerGameReport::where('game_report_id', $game->game_report_id)->get();
                    $gameUrl = \App\Models\URLHelper::getGameUrl($history, $game->id);
                    $timestamp = $game->updated_at->timestamp;
                @endphp

                <tr class="align-middle" data-timestamp="{{ $timestamp }}">
                    @if ($history->ladder->ladder_type === \App\Models\Ladder::TWO_VS_TWO)

                        @php
                            $groupedPlayerGameReports = [];
                            foreach ($playerGameReports as $playerGameReport) {
                                $team = $playerGameReport->team;
                                if ($team == null)
                                    continue;
                                $groupedPlayerGameReports[$team][] = $playerGameReport;
                            }
                        @endphp

                        @php $vs = 0; @endphp
                        @foreach ($groupedPlayerGameReports as $team => $teamPlayerGameReportArr)
                            @foreach ($teamPlayerGameReportArr as $k => $pgr)
                                @if ($vs == 2)
                                    <td class="td-versus">
                                        <div class="d-flex align-items-center justify-content-center text-center">
                                            <span class="vs">Vs</span>
                                        </div>
                                    </td>
                                @endif
                                <td class="td-player td-player-opponent">
                                    @include('ladders.components._games-player-row', [
                                        'profileUrl' => \App\Models\URLHelper::getPlayerProfileUrl($history, $pgr->player->username),
                                        'username' => $pgr->player->username,
                                        'avatar' => $pgr->player->user->getUserAvatar(),
                                        'playerGameReport' => $pgr,
                                    ])
                                </td>
                                @php $vs++; @endphp
                            @endforeach
                        @endforeach
                    @else
                        @php $vs = 0; @endphp
                        @foreach ($playerGameReports as $k => $pgr)
                            @if ($vs == 1)
                                <td class="td-versus">
                                    <div class="d-flex align-items-center justify-content-center text-center">
                                        <span class="vs">Vs</span>
                                    </div>
                                </td>
                            @endif

                            <td class="td-player td-player-opponent">
                                @include('ladders.components._games-player-row', [
                                    'profileUrl' => \App\Models\URLHelper::getPlayerProfileUrl($history, $pgr->player->username),
                                    'username' => $pgr->player->username,
                                    'avatar' => $pgr->player->user->getUserAvatar(),
                                    'playerGameReport' => $pgr,
                                ])
                            </td>
                            @php $vs++; @endphp
                        @endforeach

                    @endif

                    <td class="td-game-details">
                        <div class="d-flex align-items-center game-details">
                            <div>
                                <p class="fw-bold mb-1">{{ $game->qmMatch?->map?->description }}</p>
                                <p class="text-muted mb-0">Duration: {{ gmdate('H:i:s', $game->report->duration) }}</p>
                                <p class="text-muted mb-0">
                                    Played: {{ $game->updated_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex align-items-center">
                            @php $mapPreview = \App\Helpers\SiteHelper::getMapPreviewUrl($history, $game->qmMatch?->map?->map, $game->qmMatch?->map?->map->hash); @endphp
                            @if ($mapPreview)
                                <div class="map-preview" style="background-image:url({{ $mapPreview }})">
                                </div>
                            @endif
                        </div>
                    </td>

                    <td class="td-link">
                        <a href="{{ $gameUrl }}" class="game-link">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
