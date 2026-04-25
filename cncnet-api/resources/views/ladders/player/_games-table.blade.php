<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>

        @foreach ($games as $gameReport)

            <tr class="align-middle" data-timestamp="{{ $gameReport->gameReport->updated_at->timestamp }}">
                @if ($history->ladder->ladder_type === \App\Models\Ladder::TWO_VS_TWO)

                    @php $vs = 0; @endphp
                    @foreach ($gameReport->groupedPlayerGameReports as $team => $teamPlayerGameReportArr)
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
                                    'profileUrl' => $pgr->profileUrl,
                                    'username' => $pgr->player->username,
                                    'avatar' => $pgr->avatarUrl,
                                    'playerGameReport' => $pgr,
                                ])
                            </td>
                            @php $vs++; @endphp
                        @endforeach
                    @endforeach

                @else
                    <td class="td-player">
                        @include('ladders.components._games-player-row', [
                            'profileUrl' => $gameReport->playerGameReport->profileUrl,
                            'username' => $gameReport->player->username,
                            'avatar' => $gameReport->player->user->getUserAvatar(),
                            'playerGameReport' => $gameReport->playerGameReport,
                        ])
                    </td>
                    <td class="td-versus">
                        <div class="d-flex align-items-center justify-content-center text-center">
                            <span class="vs">Vs</span>
                        </div>
                    </td>
                    <td class="td-player td-player-opponent">
                        @if ($gameReport->opponentPlayerReport)
                            @include('ladders.components._games-player-row', [
                                'profileUrl' => $gameReport->opponentPlayerReport->profileUrl,
                                'username' => $gameReport->opponentPlayerReport->player->username,
                                'avatar' => $gameReport->opponentPlayerReport->avatarUrl,
                                'playerGameReport' => $gameReport->opponentPlayerReport,
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
                        <div class="map-preview" style="background-image:url({{ $gameReport->mapPreviewUrl }})">
                        </div>
                    </div>
                </td>

                <td class="td-link">
                    <a href="{{ $gameReport->gameUrl }}" class="game-link">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
