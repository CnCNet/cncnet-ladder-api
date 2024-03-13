<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>

        @foreach ($games as $gameReport)
            @php
                $gameUrl = \App\Models\URLHelper::getGameUrl($history, $gameReport->game_id);
                $timestamp = $gameReport->gameReport->updated_at->timestamp;

                $playerGameReport = \App\Models\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                    ->where('clan_id', '=', $gameReport->clan->id)
                    ->groupBy('won')
                    ->orderByRaw('won = false')
                    ->first();

                $clanProfileUrl = \App\Models\URLHelper::getClanProfileLadderUrl($history, $playerGameReport->clan_id);
                $playerProfileUrl = \App\Models\URLHelper::getPlayerProfileUrl($history, $playerGameReport->player->username);

                $opponentPlayerReport = \App\Models\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                    ->where('clan_id', '!=', $gameReport->clan->id)
                    ->groupBy('won')
                    ->orderByRaw('won = false')
                    ->first();

                if (isset($opponentPlayerReport) && $opponentPlayerReport) {
                    $opponentClanProfileUrl = \App\Models\URLHelper::getClanProfileLadderUrl($history, $opponentPlayerReport->clan_id);
                    $opponentPlayerProfileUrl = \App\Models\URLHelper::getPlayerProfileUrl($history, $opponentPlayerReport->player->username);
                }
            @endphp

            <tr class="align-middle" data-timestamp="{{ $timestamp }}">
                <td class="td-player">
                    @include('ladders.components._games-player-clan-row', [
                        'clanName' => $playerGameReport->clan->short,
                        'avatar' => $playerGameReport->clan->getClanAvatar(),
                        'clanProfileUrl' => $clanProfileUrl,
                        'profileUrl' => $playerProfileUrl,
                        'username' => $playerGameReport->clan->short,
                        'playerGameReport' => $playerGameReport,
                    ])
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
                            'avatar' => $opponentPlayerReport->clan->getClanAvatar(),
                            'clanProfileUrl' => $opponentClanProfileUrl,
                            'profileUrl' => $opponentPlayerProfileUrl,
                            'username' => $opponentPlayerReport->player->username,
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

                        @php
                            $mapPreview = \App\Helpers\SiteHelper::getMapPreviewUrl($history, $gameReport->game->map, $gameReport->game->hash);
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
