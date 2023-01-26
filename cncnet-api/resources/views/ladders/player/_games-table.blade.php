<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>

            @foreach ($games as $gameReport)
                @php
                    $gameUrl = \App\URLHelper::getGameUrl($history, $gameReport->game_id);
                    $timestamp = $gameReport->gameReport->updated_at->timestamp;
                    
                    $playerGameReport = \App\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                        ->where('player_id', '=', $player->id)
                        ->first();
                    
                    $playerProfileUrl = \App\URLHelper::getPlayerProfileUrl($history, $player->username);
                    
                    $opponentPlayerReport = \App\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                        ->where('player_id', '!=', $player->id)
                        ->first();
                    
                    if ($opponentPlayerReport) {
                        $opponentPlayerUrl = \App\URLHelper::getPlayerProfileUrl($history, $opponentPlayerReport->player->username);
                    }
                @endphp

                <tr class="align-middle" data-timestamp="{{ $timestamp }}">
                    <td class="td-player">
                        @include('ladders.components._games-player-row', [
                            'profileUrl' => $playerProfileUrl,
                            'username' => $playerGameReport->player->username,
                            'avatar' => $playerGameReport->player->user->getUserAvatar(),
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
                            @php $mapPreview = 'https://ladder.cncnet.org/images/maps/' . $history->ladder->abbreviation . '/' . $gameReport->hash . '.png'; @endphp
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
