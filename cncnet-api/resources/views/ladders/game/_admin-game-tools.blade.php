    <section class="game-admin">
        <div class="game-details">
            <div class="container" style="position:relative;padding: 60px 0;">
                <? $hasWash = false; ?>
                @if ($gameReport !== null)
                    @foreach ($allGameReports as $thisGameReport)
                        @if ($userIsMod && $thisGameReport->best_report && $thisGameReport->id != $gameReport->id)
                            <div class="player-vs" style="border: 2px solid blue;">
                            @elseif ($userIsMod && $thisGameReport->best_report)
                                <div class="player-vs" style="border: 6px solid green;" href="#">
                                @elseif ($userIsMod && $thisGameReport->id == $gameReport->id)
                                    <div class="player-vs" style="border: 6px solid yellow;">
                                    @elseif ($userIsMod)
                                        <div class="player-vs" style="border: 2px solid gray;">
                                        @else
                                            <div class="player-vs">
                        @endif
                        <?php $thesePlayerGameReports = $thisGameReport !== null ? $thisGameReport->playerGameReports()->get() : []; ?>
                        @foreach ($thesePlayerGameReports as $k => $pgr)
                            <?php $player = $pgr->player()->first(); ?>
                            <?php $url = '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/player/' . $player->username; ?>

                            <h3 class="game-intro">
                                <a href="{{ $url }}" title="View {{ $player->username }}'s profile"
                                    style="@if ($k == 0) order:0; @else order: 1; @endif">
                                    <span class="player">
                                        {{ $player->username ?? 'Unknown' }} <strong>
                                            @if ($pgr->points >= 0)
                                                +
                                            @endif{{ $pgr->points ?? '' }}
                                        </strong>
                                    </span>
                                </a>

                                <div class="game-status-icon" style="@if ($k == 0) order:0; @endif">
                                    @if ($pgr->won)
                                        <i class="fa fa-trophy fa-fw" style="color: #32e91e;"></i>
                                    @elseif($pgr->draw)
                                        <i class="fa fa-handshake-o fa-fw" style="color: #e96b1e;"></i>
                                    @elseif($pgr->disconnected)
                                        <i class="fa fa-plug fa-fw" style="color: #E91E63;"></i>
                                    @else
                                        <i class="fa fa-sun-o fa-fw" style="color: #00BCD4;"></i>
                                    @endif
                                </div>

                                @if ($playerGameReports->count() == 1)
                                    <a href="{{ $url }}" title="View {{ $player->username }}'s profile"
                                        style="@if ($k == 0) order:1; @endif">
                                        <span class="player">
                                            {{ $player->username ?? 'Unknown' }} <strong>
                                                @if ($pgr->points >= 0)
                                                    +
                                                @endif{{ $pgr->points ?? '' }}
                                            </strong>
                                            @if ($pgr->won)
                                                <i class="fa fa-trophy fa-fw" style="color: #E91E63;"></i>
                                            @elseif($pgr->draw)
                                                <i class="fa fa-handshake-o fa-fw" style="color: #e96b1e;"></i>
                                            @elseif($pgr->disconnected)
                                                <i class="fa fa-plug fa-fw" style="color: #E91E63;"></i>
                                            @else
                                                <i class="fa fa-sun-o fa-fw" style="color: #00BCD4;"></i>
                                            @endif
                                        </span>
                                    </a>
                                @endif
                            </h3>

                            @if ($k == 0)
                                @if ($userIsMod && $thisGameReport->id != $gameReport->id)
                                    <a class="btn btn-outline-secondary"
                                        href="{{ route('ladder.game.report', ['date' => $date, 'game' => $cncnetGame, 'gameId' => $game->id, 'reportId' => $thisGameReport->id]) }}">View</a>
                                @elseif ($userIsMod && $thisGameReport->id == $gameReport->id && !$thisGameReport->best_report)
                                    <form action="/admin/moderate/{{ $history->ladder->id }}/games/switch" class="text-center" method="POST">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input name="game_id" type="hidden" value="{{ $game->id }}" />
                                        <input name="game_report_id" type="hidden" value="{{ $thisGameReport->id }}" />
                                        <button type="submit" class="btn btn-danger">Use This One</button>
                                    </form>
                                @else
                                    <div class="vs">
                                        VS
                                    </div>
                                @endif
                            @endif
                        @endforeach

                        @if ($thisGameReport->id === $gameReport->id && $showBothZeroFix && $gameReport->fps < $history->ladder->qmLadderRules->bail_fps)
                            <div class="alert alert-info mt-4">
                                <strong>FPS too low – no points awarded.</strong><br>Minimum FPS for this ladder is {{ $history->ladder->qmLadderRules->bail_fps}}.
                            </div>
                        @endif

                        @if ($thisGameReport->id === $gameReport->id && $showBothZeroFix && $gameReport->duration < $history->ladder->qmLadderRules->bail_time)
                            <div class="alert alert-info mt-4">
                                <strong>Game duration too short – no points awarded.</strong><br>Minimum time for this ladder is {{ $history->ladder->qmLadderRules->bail_time}}.
                            </div>
                        @endif

                        @if ($thisGameReport->id === $gameReport->id && $showBothZeroFix && $userIsMod && !empty($fixedPointsPreview) && count($fixedPointsPreview) === 2)
                        <form method="POST" action="/admin/moderate/{{ $history->ladder->id }}/games/fix-points" class="text-center mt-4">
                            @csrf
                            @foreach ($fixedPointsPreview as $entry)
                                <input type="hidden" name="player_points[{{ $entry['player_id'] }}]" value="{{ $entry['calculated_points'] }}">
                            @endforeach
                            <input type="hidden" name="game_id" value="{{ $game->id }}">
                            <input type="hidden" name="game_report_id" value="{{ $gameReport->id }}">
                            <input type="hidden" name="mode" value="fix_points">
                            <button type="submit" class="btn btn-outline-secondary">
                                Fix points ({{ $fixedPointsPreview[0]['player'] }}: {{ $fixedPointsPreview[0]['calculated_points'] >= 0 ? '+' : '' }}{{ $fixedPointsPreview[0]['calculated_points'] }}, {{ $fixedPointsPreview[1]['player'] }}: {{ $fixedPointsPreview[1]['calculated_points'] >= 0 ? '+' : '' }}{{ $fixedPointsPreview[1]['calculated_points'] }})
                            </button>
                        </form>
                        @endif

                        @if ($thisGameReport->id === $gameReport->id && $showBothPositiveFix && $userIsMod)
                        <form method="POST" action="/admin/moderate/{{ $history->ladder->id }}/games/fix-points" class="text-center mt-4">
                            @csrf
                            <input type="hidden" name="game_id" value="{{ $game->id }}">
                            <input type="hidden" name="game_report_id" value="{{ $gameReport->id }}">
                            <input type="hidden" name="mode" value="zero_for_loser">
                            <button type="submit" class="btn btn-outline-secondary">
                               Set points for loser to 0
                            </button>
                        </form>
                        @endif

                        @if ($thesePlayerGameReports->count() < 1)
                            <form action="/admin/moderate/{{ $history->ladder->id }}/games/switch" class="text-center" method="POST">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input name="game_id" type="hidden" value="{{ $game->id }}" />
                                <input name="game_report_id" type="hidden" value="{{ $thisGameReport->id }}" />
                                @if ($thisGameReport->best_report)
                                    <button type="submit" class="btn btn-md btn-danger" disabled>Washed</button>
                                @else
                                    <button type="submit" class="btn btn-md btn-danger">Wash</button>
                                @endif
                            </form>
                            <?php $hasWash = true; ?>
                        @endif
            </div>
            @endforeach

            @if (!(isset($hasWash) && $hasWash) && $userIsMod)
                <form action="/admin/moderate/{{ $history->ladder->id }}/games/wash" class="text-center" method="POST">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input name="game_id" type="hidden" value="{{ $game->id }}" />
                    <button type="submit" class="btn btn-md btn-danger">Wash</button>
                </form>
            @endif

            <div class="game-details text-center">
                <div>
                    <strong>Duration:</strong> {{ gmdate('H:i:s', $gameReport->duration) }}
                </div>
                <div>
                    <strong>Average FPS:</strong> {{ $gameReport->fps }}
                </div>
                @if ($userIsMod)
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adminData">Admin Data</button>
                    </div>
                @endif
            </div>
            @endif
        </div>
        </div>
    </section>

    @include('ladders.game._modal-admin-data')
