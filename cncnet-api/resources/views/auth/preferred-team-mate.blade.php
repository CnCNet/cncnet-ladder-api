<td>
    <ul>
        @if ($player->preferredTeamMate())
        <li style="display: inline-block; margin: 0;">
            <strong class="fw-bold">
                {{ $player->preferredTeamMate()->username }}
            </strong>
        </li>

        <form style="display: inline-block; margin: 0;" method="POST" action="/account/removeTeammate">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="player_id" value="{{ $player->id }}">
            <input type="hidden" name="teammate_player_id" value="{{ $player->preferredTeamMate()->id }}">

            <button type="action" name="action" value="delete" class="btn btn-sm btn-secondary btn-size-sm reject">Remove</button>
        </form>
        @else

        @if ($player->invitesReceived)
        @foreach ($player->invitesReceived->where('status', 'pending') as $playerInvitation)
        <li>
            <div>
                Invite from <strong class="username">{{ $playerInvitation->author->username }}</strong>
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
        </li>
        @endforeach
        @endif

        @if ($player->invitesSent)
        @foreach ($player->invitesSent->where('status', 'pending') as $playerInvitation)
        <li>
            <div>
                Invite sent to <strong class="username">{{ $playerInvitation->invitedPlayer->username }}</strong>
                <form method="POST" action="/account/processPlayerInvitation">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="{{ $playerInvitation->id }}">

                    <button type="action" name="action" value="reject" class="btn btn-sm btn-secondary btn-size-sm reject">Delete
                    </button>
                </form>
            </div>
        </li>
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

                            <button type="submit" name="submit" value="invite" class="btn btn-secondary">Send Invitation</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <li>
            <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#invitePlayer_{{ $player->id }}">
                Invite a new Teammate
            </button>
        </li>
        @endif
    </ul>
</td>