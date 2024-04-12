<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>
            @foreach ($games as $game)
                <?php
                
                $gameUrl = \App\Models\URLHelper::getGameUrl($history, $game->id);
                $timestamp = $game->updated_at->timestamp;
                
                $playerGameReport = \App\Models\PlayerGameReport::where('game_report_id', $game->game_report_id)
                    ->where('clan_id', '=', $game->report->clan_id)
                    ->first();
                
                if ($playerGameReport) {
                    $clanProfileUrl = \App\Models\URLHelper::getClanProfileLadderUrl($history, $playerGameReport->clan_id);
                    $playerProfileUrl = \App\Models\URLHelper::getPlayerProfileUrl($history, $playerGameReport->player->username);
                
                    if ($clanProfileUrl == null || $playerProfileUrl == null) {
                        continue;
                    }
                    $opponentPlayerReport = \App\Models\PlayerGameReport::where('game_report_id', $game->game_report_id)
                        ->where('clan_id', '!=', $playerGameReport->clan->id)
                        ->first();
                
                    if ($opponentPlayerReport) {
                        $opponentClanProfileUrl = \App\Models\URLHelper::getClanProfileLadderUrl($history, $opponentPlayerReport->clan_id);
                        $opponentPlayerProfileUrl = \App\Models\URLHelper::getPlayerProfileUrl($history, $opponentPlayerReport->player->username);
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
                        @if (isset($opponentPlayerReport) && $opponentPlayerReport)
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
                                <p class="fw-bold mb-1">{{ $game->qmMatch->map->map->name }}</p>
                                <p class="text-muted mb-0">Duration: {{ gmdate('H:i:s', $game->report->duration) }}</p>
                                <p class="text-muted mb-0">
                                    Played: {{ $game->report->updated_at->diffForHumans() }}
                                </p>
                                <p class="text-muted mb-0">
                                    FPS: {{ $game->report->fps }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td>
                        test5
                        <div class="d-flex align-items-center">
                            @php
                                $mapPreview = \App\Helpers\SiteHelper::getMapPreviewUrl($history, $game->map, $game->hash);
                            @endphp

                            <div class="map-preview" style="background-image:url({{ $mapPreview }})">test5
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
