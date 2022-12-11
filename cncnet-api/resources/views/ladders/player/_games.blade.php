<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>

            @foreach ($games as $gameReport)
                @php
                    $gameUrl = \App\URLHelper::getGameUrl($history, $gameReport->game_id);
                    
                    $playerGameReport = \App\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                        ->where('player_id', '=', $player->id)
                        ->first();
                    
                    $playerProfileUrl = \App\URLHelper::getPlayerProfileUrl($history, $player->username);
                    
                    $opponentPlayerReport = \App\PlayerGameReport::where('game_report_id', $gameReport->game_report_id)
                        ->where('player_id', '!=', $player->id)
                        ->first();
                    
                    $opponentPlayerUrl = \App\URLHelper::getPlayerProfileUrl($history, $opponentPlayerReport->player->username);
                    
                @endphp

                <tr class="align-middle">
                    <td class="td-player">
                        @include('ladders.player._games-player-row', [
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
                        @include('ladders.player._games-player-row', [
                            'profileUrl' => $opponentPlayerUrl,
                            'username' => $opponentPlayerReport->player->username,
                            'avatar' => $opponentPlayerReport->player->user->getUserAvatar(),
                            'playerGameReport' => $opponentPlayerReport,
                        ])
                    </td>

                    <td class="td-game-details">
                        <div class="d-flex align-items-center game-details">
                            <div>
                                <p class="fw-bold mb-1">{{ $gameReport->scen }}</p>
                                <p class="text-muted mb-0">{{ gmdate('H:i:s', $gameReport->duration) }}</p>
                                <p class="text-muted mb-0">
                                    {{ $gameReport->created_at->diffForHumans() }}
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


                {{-- @foreach ($pgr as $k => $gameReportPlayer)
                    @php
                    @endphp

                    @if ($k == 0)
                        <tr class="align-middle">
                    @endif

                    @if ($k == 0)
                        <td class="td-player">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <a href="{{ $playerUrl }}" title="View {{ $gameReportPlayer->player->username }}'s profile">
                                        @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 45])
                                    </a>
                                </div>

                                <div class="player-row">
                                    <div class="player-username">
                                        <a href="{{ $playerUrl }}" title="View {{ $gameReportPlayer->player->username }}'s profile">
                                            <p class="fw-bold mb-1">{{ $gameReportPlayer->player->username }}</p>
                                        </a>
                                    </div>

                                    @if ($gameReportPlayer->stats)
                                        @php $playerStats2 = \App\Stats2::where("id", $gameReportPlayer->stats->id)->first(); @endphp
                                        @php $playerCountry = $playerStats2->faction($history->ladder->game, $gameReportPlayer->stats->cty); @endphp
                                        <div class="player-faction player-faction-{{ $playerCountry }}"></div>

                                        <div class="game-status status-{{ $gameReportPlayer->won ? 'won' : 'lost' }}">
                                            <span class="status-text">
                                                {{ $gameReportPlayer->won == true ? 'Won' : 'Lost' }}
                                            </span>

                                            <span class="points">
                                                {{ $gameReportPlayer->points >= 0 ? "+$gameReportPlayer->points" : $gameReportPlayer->points }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    @else
                        <td class="td-player">
                            <div class="d-flex align-items-center">
                                <div class="player-row">
                                    @if ($gameReportPlayer->stats)
                                        <div class="game-status status-{{ $gameReportPlayer->won ? 'won' : 'lost' }}">
                                            <span class="status-text">
                                                {{ $gameReportPlayer->won == true ? 'Won' : 'Lost' }}
                                            </span>

                                            <span class="points">
                                                {{ $gameReportPlayer->points >= 0 ? "+$gameReportPlayer->points" : $gameReportPlayer->points }}
                                            </span>
                                        </div>
                                        @php $playerStats2 = \App\Stats2::where("id", $gameReportPlayer->stats->id)->first(); @endphp
                                        @php $playerCountry = $playerStats2->faction($history->ladder->game, $gameReportPlayer->stats->cty); @endphp
                                        <div class="player-faction player-faction-{{ $playerCountry }}"></div>
                                    @endif
                                    <div class="ms-3 me-3">
                                        <a href="{{ $playerUrl }}" title="View {{ $gameReportPlayer->player->username }}'s profile">
                                            @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 45])
                                        </a>
                                    </div>
                                    <div class="player-username">
                                        <a href="{{ $playerUrl }}" title="View {{ $gameReportPlayer->player->username }}'s profile">
                                            <p class="fw-bold mb-1">{{ $gameReportPlayer->player->username }}</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    @endif
                    @if ($k == 0)
                        <td class="td-versus">
                            <div class="d-flex align-items-center justify-content-center text-center">
                                <span class="vs">Vs</span>
                            </div>
                        </td>
                    @endif
                    @if ($k == 1)
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="fw-bold mb-1">{{ $gameReport->scen }}</p>
                                    <p class="text-muted mb-0">{{ gmdate('H:i:s', $gameReport->duration) }}</p>
                                    <p class="text-muted mb-0">
                                        {{ $gameReport->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <a href="{{ $gameUrl }}" class="player-link p-2">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </td>
                        </tr>
                    @endif
                @endforeach --}}
            @endforeach
        </tbody>
    </table>
</div>
