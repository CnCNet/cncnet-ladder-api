@if ($userIsMod)
    <div class="modal fade modal-xl" id="adminData" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admin Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
                                    @if ($thisGameReport->player)
                                        {{ $thisGameReport->reporter->username }}
                                    @endif
                                    @if ($thisGameReport->manual_report)
                                        <i class="fa fa-align-justify fa-fw" style="color: #fff;"></i>
                                    @endif
                                </div>
                                <div class="col-md-2  admin-data">
                                    {{ $thisGameReport->pings_received }}/{{ $thisGameReport->pings_sent }}
                                </div>
                                <div class="col-md-2  admin-data">
                                    @if ($thisGameReport->oos)
                                        Yes
                                    @else
                                        No
                                    @endif
                                </div>
                                <div class="col-md-2  admin-data">
                                    @if ($thisGameReport->finished)
                                        Yes
                                    @else
                                        No
                                    @endif
                                </div>
                                <div class="col-md-2 admin-data">
                                    <h5><a href="/dmp/{{ $game->id }}.{{ $history->ladder->id }}.{{ $thisGameReport->player_id }}.dmp">dmp</a>
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
                                                {{ $qmStat->ipAddress->address }}:{{ $qmStat->port }}
                                                <strong>{{ $qmStat->rtt }}ms</strong>
                                            @endif
                                        @endforeach
                                        <hr>
                                        @foreach ($qmMatchStates as $qmState)
                                            @if ($qmState->player_id == $pgr->player_id)
                                                <h5>{{ $qmState->created_at }}</h5>
                                                <strong>{{ $qmState->state->name }}</strong>
                                            @endif
                                        @endforeach

                                        <hr>
                                        @foreach ($qmMatchPlayers as $qmp)
                                            @if ($qmp->player_id == $pgr->player_id)
                                                <h5>Version:</h5>
                                                {{ $qmp->version->value }} {{ $qmp->platform->value }}

                                                <h5>Queue Time:</h5>
                                                {{ $game->qmMatch->created_at->diff($qmp->created_at)->format('%i') }}
                                                Minutes

                                                <h5>DDraw Hash:</h5>
                                                @if ($qmp->ddraw)
                                                    <span style="word-wrap: break-word">
                                                        {{ $qmp->ddraw->value }}
                                                    </span>
                                                @endif
                                            @endif
                                        @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif
