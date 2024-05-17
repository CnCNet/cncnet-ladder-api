<div class="row">
    <div class="col-md-12 mb-5 mt-5">
        <h4><i class="bi bi-flag-fill icon-clan pe-3"></i> My Preferred Teammates</h4>

        @if ($activeHandles->count() > 0)
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Username</th>
                        <th scope="col">Username Active on Ladder</th>
                        <th scope="col">Preferred Teammate</th>
                        <th scope="col">Toggle Player</th>
                        <th scope="col">OBS Info</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($players as $player)
                    <tr>
                        <td>
                            <div class="d-flex">
                                <div class="username me-2">
                                    <i class="icon icon-game icon-{{ $player->ladder()->first()->abbreviation }} icon-sm"></i>
                                </div>
                                <p>
                                    {{ $player->username }}
                                </p>
                            </div>
                        </td>
                        <td>
                            @if ($activeHandles->where('player_id', $player->id)->count() > 0)
                            <strong class="highlight">Active</strong>
                            @else
                            <span>Inactive</span>
                            @endif
                        </td>
                        <td>
                            @if ($player->preferredTeamMate())
                            <strong class="fw-bold">
                                {{ $player->preferredTeamMate()->username }}
                            </strong>
                            @else

                            @if ($player->invitesReceived)
                            @foreach ($player->invitesReceived as $playerInvitation)
                            <div>
                                <div>
                                    <strong class="username">Invite from: {{ $playerInvitation->author->username }}</strong>
                                </div>
                                <div>
                                    <form method="POST" action="/account/processPlayerInvitation">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="id" value="{{ $playerInvitation->id }}">

                                        <button type="action" name="action" value="accept" class="btn btn-sm btn-primary btn-size-sm accept">Accept
                                        </button>

                                        <button type="action" name="action" value="reject" class="btn btn-sm btn-secondary btn-size-sm reject">Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                            @endif

                            <div class="modal fade" tabindex="-1" id="invitePlayer_{{ $player->id }}" tabIndex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Send Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <form method="POST" action="/account/sendPlayerInvitation">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="author_player_id" value="{{ $player->id }}">
                                                <input type="hidden" name="ladder_id" value="{{ $ladder->id }}">

                                                <label for="invite_player_name"><strong>Player name</strong></label>
                                                <input type="text" name="invite_player_name" class="form-control border" placeholder="">

                                                <button type="submit" name="submit" value="invite" class="btn">Send Invitation</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#invitePlayer_{{ $player->id }}">
                                    Invite a new Teammate
                                </button>
                            </div>
                            @endif
                        </td>
                        <td>
                            <form id="username-status" class="form-inline" method="POST" action="username-status">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input type="hidden" name="username" value="{{ $player->username }}" />

                                @php $active = $activeHandles->where('player_id', $player->id)->count() > 0; @endphp
                                <button type="submit" class="btn btn-sm {{ $active ? 'btn-outline' : 'btn-outline' }} btn-size-sm">
                                    {{ $active ? 'Stop playing with Username' : 'Play with Username' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <a href="/api/v1/ladder/{{ $player->ladder()->first()->abbreviation }}/player/{{ $player->username }}/webview" target="_blank" class="btn btn-md me-3 btn-size-md">
                                OBS Stream Profile
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="lead">
            You have to register and activate a username below for this ladder.
        </p>
        @endif
    </div>
</div>
</div>