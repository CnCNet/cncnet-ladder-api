<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>
            @foreach ($games as $gameReport)
                <?php
                
                $gameUrl = \App\URLHelper::getGameUrl($history, $gameReport->id);
                $timestamp = $gameReport->updated_at->timestamp;
                
                $playerGameReport = \App\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                    ->where('clan_id', '=', $gameReport->report->clan_id)
                    ->first();
                
                if ($playerGameReport) {
                    $clanProfileUrl = \App\URLHelper::getClanProfileLadderUrl($history, $playerGameReport->clan_id);
                    $playerProfileUrl = \App\URLHelper::getPlayerProfileUrl($history, $playerGameReport->player->username);
                
                    $opponentPlayerReport = \App\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                        ->where('clan_id', '!=', $playerGameReport->clan->id)
                        ->first();
                
                    if ($opponentPlayerReport) {
                        $opponentClanProfileUrl = \App\URLHelper::getClanProfileLadderUrl($history, $opponentPlayerReport->clan_id);
                        $opponentPlayerProfileUrl = \App\URLHelper::getPlayerProfileUrl($history, $opponentPlayerReport->player->username);
                    }
                }
                ?>

                <tr class="align-middle" data-timestamp="{{ $timestamp }}">
                    <td class="td-player">
                        @if ($playerGameReport)
                            @include('ladders.components._games-player-clan-row', [
                                'clanName' => $playerGameReport->clan->short,
                                'clanProfileUrl' => $clanProfileUrl,
                                'profileUrl' => $playerProfileUrl,
                                'username' => $playerGameReport->player->username,
                                'avatar' => $playerGameReport->clan->getClanAvatar(),
                                'playerGameReport' => $playerGameReport,
                            ])
                        @endif
                    </td>

                    <td class="td-versus">
                        <div class="d-flex align-items-center justify-content-center text-center">
                            <span class="vs">Vs</span>
                        </div>
                    </td>

                    <td class="td-player td-player-opponent">
                        @if ($opponentPlayerReport)
                            @include('ladders.components._games-player-clan-row', [
                                'clanName' => $opponentPlayerReport->clan->short,
                                'clanProfileUrl' => $opponentClanProfileUrl,
                                'profileUrl' => $opponentPlayerProfileUrl,
                                'username' => $opponentPlayerReport->player->username,
                                'avatar' => $opponentPlayerReport->clan->getClanAvatar(),
                                'playerGameReport' => $opponentPlayerReport,
                            ])
                        @endif
                    </td>

                    <td class="td-game-details">
                        <div class="d-flex align-items-center game-details">
                            <div>
                                <p class="fw-bold mb-1">{{ $gameReport->scen }}</p>
                                <p class="text-muted mb-0">Duration: {{ gmdate('H:i:s', $gameReport->report->duration) }}</p>
                                <p class="text-muted mb-0">
                                    Played: {{ $gameReport->report->updated_at->diffForHumans() }}
                                </p>
                                <p class="text-muted mb-0">
                                    FPS: {{ $gameReport->report->fps }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex align-items-center">
                            @php $mapPreview = 'https://ladder.cncnet.org/images/maps/' . $history->ladder->game . '/' . $gameReport->hash . '.png'; @endphp
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
