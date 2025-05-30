<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>

        @foreach ($games as $gameReport)


            @php
                $gameUrl = \App\Models\URLHelper::getGameUrl($history, $gameReport->game_id);
                $timestamp = $gameReport->gameReport->updated_at->timestamp;
            @endphp


            <tr class="align-middle" data-timestamp="{{ $timestamp }}">
                @if ($history->ladder->ladder_type === \App\Models\Ladder::TWO_VS_TWO)

                    @php
                        $playerGameReports = \App\Models\PlayerGameReport::query()
                        ->where('game_report_id', $gameReport->game_report_id)
                        ->get();
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
                    @php
                        $playerGameReport = \App\Models\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                            ->where('player_id', '=', $player->id)
                            ->first();
                        $playerProfileUrl = \App\Models\URLHelper::getPlayerProfileUrl($history, $player->username);
                        $opponentPlayerReport = \App\Models\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                            ->where('player_id', '!=', $player->id)
                            ->first();
                        if ($opponentPlayerReport) {
                            $opponentPlayerUrl = \App\Models\URLHelper::getPlayerProfileUrl($history, $opponentPlayerReport->player->username);
                        }
                    @endphp

                    <td class="td-player">
                        @include('ladders.components._games-player-row', [
                            'profileUrl' => $playerProfileUrl,
                            'username' => $gameReport->player->username,
                            'avatar' => $gameReport->player->user->getUserAvatar(),
                            'playerGameReport' => $playerGameReport,
                        ])
                    </td>
                    <td class="td-versus">
                        <div class="d-flex align-items-center justify-content-center text-center">
                            <span class="vs">Vs</span>
                        </div>
                    </td>
                    <td class="td-player td-player-opponent">
                        @if ($opponentPlayerReport)
                            @include('ladders.components._games-player-row', [
                                'profileUrl' => $opponentPlayerUrl,
                                'username' => $opponentPlayerReport->player->username,
                                'avatar' => $opponentPlayerReport->player->user->getUserAvatar(),
                                'playerGameReport' => $opponentPlayerReport,
                            ])
                        @endif
                    </td>
                @endif


                <td class="td-game-details">
                    <div class="d-flex align-items-center game-details">
                        <div>
                            <p class="fw-bold mb-1">{{ $gameReport->scen }}</p>
                            <p class="text-muted mb-0">Duration: {{ gmdate('H:i:s', $gameReport->duration) }}</p>
                            <p class="text-muted mb-0">
                                Played: {{ $gameReport->gameReport->updated_at->diffForHumans() }}
                            </p>
                            <p class="text-muted mb-0">
                                FPS: {{ $gameReport->fps }}
                            </p>
                        </div>
                    </div>
                </td>

                <td>
                    <div class="d-flex align-items-center">
                        @php
                            $mapPreview = \App\Helpers\SiteHelper::getMapPreviewUrl($history, $gameReport->gameReport->game->map, $gameReport->gameReport->game->hash);
                        @endphp
                        <div class="map-preview" style="background-image:url({{ $mapPreview }})">
                        </div>
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
