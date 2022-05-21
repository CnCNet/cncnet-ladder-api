@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
    /images/feature/feature-{{ $history->ladder->abbreviation }}.jpg
@endsection

@section('feature')
    <div class="game">
        <div class="feature-background sub-feature-background">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h1>
                            {{ $history->ladder->name }}
                        </h1>
                        <p>
                            CnCNet Ladders <strong>1vs1</strong>
                        </p>
                        <p>
                            <a href="{{ '/ladder/' . $history->short . '/' . $history->ladder->abbreviation }}"
                                class="previous-link">
                                <i class="fa fa-caret-left" aria-hidden="true"></i>
                                <i class="fa fa-caret-left" aria-hidden="true"></i>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <?php $g = $history->ladder()->first()->abbreviation; ?>

    <div class="game {{ $g }}">

        <section class="game-statistics">
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
                                <?php $gameStats = $pgr->stats; ?>
                                @if ($gameStats != null)
                                    <div
                                        class="hidden-xs faction faction-{{ $gameStats->faction($history->ladder->game, $gameStats->cty) }} @if ($k & 1) faction-right @else faction-left @endif">
                                    </div>
                                @endif

                                <?php $player = $pgr->player()->first(); ?>
                                <?php $url = '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/player/' . $player->username; ?>

                                <h3 class="game-intro">
                                    <a href="{{ $url }}" title="View {{ $player->username }}'s profile"
                                        style="@if ($k == 0) order:0; @else order: 1; @endif">
                                        <span class="player">
                                            {{ $player->username }} <strong>
                                                @if ($pgr->points >= 0)
                                                    +
                                                @endif{{ $pgr->points }}
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
                                                {{ $player->username }} <strong>
                                                    @if ($pgr->points >= 0)
                                                        +
                                                    @endif{{ $pgr->points }}
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
                                        <?php $url = action([\App\Http\Controllers\LadderController::class, 'getLadderGame'], ['date' => $date, 'game' => $cncnetGame, 'gameId' => $game->id, 'reportId' => $thisGameReport->id]); ?>
                                        <a class="btn btn-sm btn-primary" href="{{ $url }}">View</a>
                                    @elseif ($userIsMod && $thisGameReport->id == $gameReport->id && !$thisGameReport->best_report)
                                        <form action="/admin/moderate/{{ $history->ladder->id }}/games/switch"
                                            class="text-center" method="POST">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input name="game_id" type="hidden" value="{{ $game->id }}" />
                                            <input name="game_report_id" type="hidden"
                                                value="{{ $thisGameReport->id }}" />
                                            <button type="submit" class="btn btn-md btn-danger">Use This One</button>
                                        </form>
                                    @else
                                        <div class="vs">
                                            VS
                                        </div>
                                    @endif
                                @endif
                            @endforeach

                            @if ($thesePlayerGameReports->count() < 1)
                                <form action="/admin/moderate/{{ $history->ladder->id }}/games/switch"
                                    class="text-center" method="POST">
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
                    <form action="/admin/moderate/{{ $history->ladder->id }}/games/wash" class="text-center"
                        method="POST">
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
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#adminData">Admin
                                Data</button>
                        </div>
                    @endif
                </div>
                @endif
            </div>
    </div>
    </section>

    <section class="dark-texture">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h3>Map - {{ $game->scen }} </h3>
                    <?php $map = \App\Models\Map::where('hash', '=', $game->hash)->first(); ?>
                    @if ($map)
                        <div class="feature-map">
                            <img src="/images/maps/{{ $history->ladder->abbreviation }}/{{ $map->hash . '.png' }}">
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <h3>Match Setup</h3>
                    <ul class="list-unstyled game-details-list">
                        @if ($g !== 'ra')
                            <li><strong>Short Game:</strong> {{ $game->shrt ? 'On' : 'Off' }}</li>
                            <li><strong>Superweapons:</strong> {{ $game->supr ? 'On' : 'Off' }}</li>
                            <li><strong>Crates:</strong> {{ $game->crat ? 'On' : 'Off' }}</li>
                            <li><strong>MCV Redeploy:</strong> {{ $game->bamr & 1 ? 'On' : 'Off' }}</li>
                            <li><strong>Unit Count Start:</strong> {{ $game->unit ? $game->unit : 0 }}</li>
                            <li><strong>Players in Game:</strong> {{ $game->plrs ? $game->plrs : 0 }}</li>
                            <li><strong>Build off Ally Conyard:</strong> {{ $game->bamr & 2 ? 'On' : 'Off' }}</li>
                            <li><strong>Credits:</strong> {{ $game->cred }}</li>
                        @endif

                        @if ($gameReport !== null)
                            <li><strong>Reconnection Error (OOS):</strong> {{ $gameReport->oos ? 'Yes' : 'No' }}</li>
                            <li><strong>Disconnection:</strong> {{ $gameReport->disconnected() ? 'Yes' : 'No' }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="dark-texture">
        <div class="container">
            <div class="row">
                @foreach ($playerGameReports as $pgr)
                    <?php $gameStats = $pgr->stats; ?>
                    <?php $player = $pgr->player()->first(); ?>
                    <?php $playerCache = $player->playerCache($history->id); ?>
                    <?php $rank = $playerCache ? $playerCache->rank() : 0; ?>
                    <?php $points = $playerCache ? $playerCache->points : 0; ?>

                    <div class="col-md-6">
                        <a href="/ladder/{{ $history->short . '/' . $history->ladder->abbreviation }}/player/{{ $player->username }}"
                            class="profile-link">
                            <div class="profile-detail">
                                <?php $badge = \App\Models\Player::getBadge($playerCache ? $playerCache->percentile : 0); ?>
                                <div class="player-badge badge-1x">
                                    <img src="/images/badges/{{ $badge['badge'] . '.png' }}" style="max-width:100%">
                                </div>
                                <h3>Rank #{{ $rank }}</h3>
                                <p class="username"><i class="fa fa-user fa-fw"></i> {{ $player->username }}</p>
                                <p class="points"><i class="fa fa-bolt fa-fw"></i> {{ $points }}</p>
                                @if ($gameStats !== null)
                                    <p class="points"><strong>Funds Left: </strong> {{ $gameStats->crd }}
                                    </p>
                                    <p class="colour player-panel-{{ $gameStats->colour($gameStats->col) }}"
                                        style="width:25px;height:25px;"></p>
                                    <div class="country">
                                        <span
                                            class="flag-icon flag-icon-{{ $gameStats->country($gameStats->cty) }}"></span>
                                    </div>
                                @endif
                            </div>
                        </a>

                        @if ($gameStats !== null)
                            <div class="player-colour player-panel-{{ $gameStats->colour($gameStats->col) }}"></div>
                            <div class="player-stats-panel profile-stats-breakdown clearfix">
                                <?php $last_heap = 'Z'; ?>
                                <div>
                                    @foreach ($heaps as $heap)
                                        @if (substr($heap->name, 2, 1) != $last_heap)
                                </div>
                                <div class="row stats-row">
                        @endif
                        <div class="col-md-12">
                            <h4>{{ $heap->description }}</h4>
                            <div class="clearfix stats-box">
                                @foreach ($gameStats->gameObjectCounts as $goc)
                                    @if ($goc->countableGameObject->heap_name == $heap->name && $goc->countableGameObject->cameo != '')
                                        <div
                                            class="{{ $g }}-cameo cameo-tile cameo-{{ $goc->countableGameObject->cameo }}">
                                            <span class="number">{{ $goc->count }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <?php $last_heap = substr($heap->name, 2, 1); ?>
                @endforeach
            </div>
        </div>
        @endif
        </div>
        @endforeach
        </div>
    </section>
    </div>

    @if ($userIsMod)
        <div class="modal fade" id="adminData" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <h3 class="modal-title">Admin Data</h3>
                    </div>
                    <div class="modal-body clearfix">
                        <div class="container-fluid">
                            @if ($gameReport !== null)
                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <h4>Game Reports</h4>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-1 admin-data">
                                    </div>
                                    <div class="col-md-3 admin-data">
                                    </div>
                                    <div class="col-md-2 admin-data">
                                        <h5>Pings</h5>
                                    </div>
                                    <div class="col-md-2 admin-data">
                                        <h5>Recon</h5>
                                    </div>
                                    <div class="col-md-2 admin-data">
                                        <h5>Finished</h5>
                                    </div>
                                </div>
                                @foreach ($allGameReports as $thisGameReport)
                                    <div class="row">
                                        <div class="col-md-1 admin-data">
                                            @if ($thisGameReport->best_report)
                                                <h5><i class="fa fa-check-square fa-fw" style="color: #fff;"></i></h5>
                                            @else
                                                <h5><i class="fa fa-square fa-fw" style="color: #fff;"></i></h5>
                                            @endif
                                        </div>
                                        <div class="col-md-3 admin-data">
                                            <h5>
                                                @if ($thisGameReport->player)
                                                    {{ $thisGameReport->reporter->username }}
                                                @endif
                                                @if ($thisGameReport->manual_report)
                                                    <i class="fa fa-align-justify fa-fw" style="color: #fff;"></i>
                                                @endif
                                            </h5>
                                        </div>
                                        <div class="col-md-2  admin-data">
                                            <h5>{{ $thisGameReport->pings_received }}/{{ $thisGameReport->pings_sent }}
                                            </h5>
                                        </div>
                                        <div class="col-md-2  admin-data">
                                            <h5>
                                                @if ($thisGameReport->oos)
                                                    Yes
                                                @else
                                                    No
                                                @endif
                                            </h5>
                                        </div>
                                        <div class="col-md-2  admin-data">
                                            <h5>
                                                @if ($thisGameReport->finished)
                                                    Yes
                                                @else
                                                    No
                                                @endif
                                            </h5>
                                        </div>
                                        <div class="col-md-2 admin-data">
                                            <h5><a
                                                    href="/dmp/{{ $game->id }}.{{ $history->ladder->id }}.{{ $thisGameReport->player_id }}.dmp">dmp</a>
                                            </h5>
                                        </div>
                                    </div>
                                @endforeach
                                <hr>

                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <h4>Connection Stats</h4>
                                    </div>
                                </div>
                                <div class="row">
                                    @foreach ($playerGameReports as $pgr)
                                        <div class="col-md-6 admin-data">
                                            <h4>{{ $pgr->player->username }}</h5>

                                                @foreach ($qmConnectionStats as $qmStat)
                                                    @if ($pgr->player_id == $qmStat->player_id)
                                                        <h5>{{ $qmStat->ipAddress->address }}:{{ $qmStat->port }}
                                                            <strong>{{ $qmStat->rtt }}ms</strong>
                                                        </h5>
                                                    @endif
                                                @endforeach
                                                <hr>
                                                @foreach ($qmMatchStates as $qmState)
                                                    @if ($qmState->player_id == $pgr->player_id)
                                                        <h5>{{ $qmState->created_at }}
                                                            <strong>{{ $qmState->state->name }}</strong>
                                                        </h5>
                                                    @endif
                                                @endforeach
                                                <hr>
                                                @foreach ($qmMatchPlayers as $qmp)
                                                    @if ($qmp->player_id == $pgr->player_id)
                                                        <h5>Version: {{ $qmp->version->value }}
                                                            {{ $qmp->platform->value }}</h5>
                                                        <h5>Queue Time:
                                                            {{ $game->qmMatch->created_at->diff($qmp->created_at)->format('%i') }}
                                                            Minutes</h5>
                                                        <h5 style="overflow-wrap: break-word;">DDraw Hash: @if ($qmp->ddraw)
                                                                {{ $qmp->ddraw->value }}
                                                            @endif
                                                        </h5>
                                                    @endif
                                                @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer" style="border:none;">
                        <button type="button" class="btn btn-primary btn-lg" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@if ($history->ends > Carbon\Carbon::now())
    @include('components.countdown', ['target' => $history->ends->toISO8601String()])
@endif
