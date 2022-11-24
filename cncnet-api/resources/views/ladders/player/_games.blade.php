<div class="table-responsive">
    <table class="games-table table align-middle mb-0">
        <tbody>
            @foreach ($games as $game)
                @php $pgr = \App\PlayerGameReport::where('game_report_id', $game->game_report_id)->get(); @endphp
                @php $gr = \App\GameReport::where('id', $game->game_report_id)->first(); @endphp
                @php $gameUrl = \App\URLHelper::getGameUrl($history, $game->id); @endphp

                @foreach ($pgr as $k => $gamePlayer)
                    @php $player = $gamePlayer->player()->first(); @endphp
                    @php $playerUrl = \App\URLHelper::getPlayerProfileUrl($history, $gamePlayer->player->username); @endphp

                    @if ($k == 0)
                        <tr class="align-middle">
                    @endif

                    @if ($k == 0)
                        <td class="td-player">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <a href="{{ $playerUrl }}" title="View {{ $gamePlayer->player->username }}'s profile">
                                        @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 45])
                                    </a>
                                </div>

                                <div class="player-row">
                                    <div class="player-username">
                                        <a href="{{ $playerUrl }}" title="View {{ $gamePlayer->player->username }}'s profile">
                                            <p class="fw-bold mb-1">{{ $gamePlayer->player->username }}</p>
                                        </a>
                                    </div>

                                    @if ($gamePlayer->stats)
                                        @php $playerStats2 = \App\Stats2::where("id", $gamePlayer->stats->id)->first(); @endphp
                                        @php $playerCountry = $playerStats2->faction($history->ladder->game, $gamePlayer->stats->cty); @endphp
                                        <div class="player-faction player-faction-{{ $playerCountry }}"></div>

                                        <div class="game-status status-{{ $gamePlayer->won ? 'won' : 'lost' }}">
                                            <span class="status-text">
                                                {{ $gamePlayer->won == true ? 'Won' : 'Lost' }}
                                            </span>

                                            <span class="points">
                                                {{ $gamePlayer->points >= 0 ? "+$gamePlayer->points" : $gamePlayer->points }}
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
                                    @if ($gamePlayer->stats)
                                        <div class="game-status status-{{ $gamePlayer->won ? 'won' : 'lost' }}">
                                            <span class="status-text">
                                                {{ $gamePlayer->won == true ? 'Won' : 'Lost' }}
                                            </span>

                                            <span class="points">
                                                {{ $gamePlayer->points >= 0 ? "+$gamePlayer->points" : $gamePlayer->points }}
                                            </span>
                                        </div>
                                        @php $playerStats2 = \App\Stats2::where("id", $gamePlayer->stats->id)->first(); @endphp
                                        @php $playerCountry = $playerStats2->faction($history->ladder->game, $gamePlayer->stats->cty); @endphp
                                        <div class="player-faction player-faction-{{ $playerCountry }}"></div>
                                    @endif
                                    <div class="ms-3 me-3">
                                        <a href="{{ $playerUrl }}" title="View {{ $gamePlayer->player->username }}'s profile">
                                            @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 45])
                                        </a>
                                    </div>
                                    <div class="player-username">
                                        <a href="{{ $playerUrl }}" title="View {{ $gamePlayer->player->username }}'s profile">
                                            <p class="fw-bold mb-1">{{ $gamePlayer->player->username }}</p>
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
                                    <p class="fw-bold mb-1">{{ $game->scen }}</p>
                                    <p class="text-muted mb-0"> {{ gmdate('H:i:s', $game->duration) }}</p>
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
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
