<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>
            @foreach ($games as $game)
                <?php
                
                $gameUrl = \App\URLHelper::getGameUrl($history, $game->id);
                $playerGameReports = \App\PlayerGameReport::where('game_report_id', $game->game_report_id)->get();
                $timestamp = $game->updated_at->timestamp;
                $teamAReport = null;
                $teamBReport = null;
                
                foreach ($playerGameReports as $k => $pgr) {
                    if ($k == 0) {
                        $teamAReport = $pgr;
                    } else {
                        $teamBReport = $pgr;
                    }
                }
                
                try {
                    $playerProfileUrl = \App\URLHelper::getPlayerProfileUrl($history, $teamAReport->player->username);
                    $opponentPlayerUrl = \App\URLHelper::getPlayerProfileUrl($history, $teamBReport->player->username);
                } catch (Exception $ex) {
                    # Stats most likely washed, skip
                    continue;
                }
                ?>

                <tr class="align-middle" data-timestamp="{{ $timestamp }}">
                    <td class="td-player">
                        @include('ladders.components._games-player-row', [
                            'profileUrl' => $playerProfileUrl,
                            'username' => $teamAReport->player->username,
                            'avatar' => $teamAReport->player->user->getUserAvatar(),
                            'playerGameReport' => $teamAReport,
                        ])
                    </td>

                    <td class="td-versus">
                        <div class="d-flex align-items-center justify-content-center text-center">
                            <span class="vs">Vs</span>
                        </div>
                    </td>

                    <td class="td-player td-player-opponent">
                        @include('ladders.components._games-player-row', [
                            'profileUrl' => $opponentPlayerUrl,
                            'username' => $teamBReport->player->username,
                            'avatar' => $teamBReport->player->user->getUserAvatar(),
                            'playerGameReport' => $teamBReport,
                        ])
                    </td>

                    <td class="td-game-details">
                        <div class="d-flex align-items-center game-details">
                            <div>
                                <p class="fw-bold mb-1">{{ $game->scen }}</p>
                                <p class="text-muted mb-0">Duration: {{ gmdate('H:i:s', $game->report->duration) }}</p>
                                <p class="text-muted mb-0">
                                    Played: {{ $game->updated_at->diffForHumans() }}
                                </p>
                                <p class="text-muted mb-0">
                                    FPS: {{ $teamAReport->game->fps }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex align-items-center">
                            @php $imageHash = $game->map->image_hash ?? $game->map->hash; @endphp
                            @php $mapPreview = 'https://ladder.cncnet.org/images/maps/' . $history->ladder->game . '/' . $imageHash . '.png'; @endphp
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
