<div class="col-md-12 mb-5 mt-5">
    <h4><i class="bi bi-flag-fill icon-clan pe-3"></i> My Active Clans</h4>

    @if ($activeHandles->count() > 0)
    <p>
        Please note: You are allowed to be in {{ $ladder->qmLadderRules->max_active_players }}
        clans at a time. If you have
        played
        in any games in a clan this month, you may not leave that clan until next month.<br />
    </p>

    <div class="clan-listings mb-5">
        @if ($activePlayersNotInAClan !== null && count($activePlayersNotInAClan) > 0)
        <a href="#" class="btn btn-primary btn-size-md" data-bs-toggle="modal" data-bs-target="#newClan">
            Create a new Clan?
        </a>
        @endif
    </div>

    <div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Clan</th>
                        <th scope="col">Edit</th>
                        <th scope="col">My Role</th>
                        <th scope="col">All Members</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clanPlayers as $clanPlayer)
                    @if ($clanPlayer->clan)
                    <tr>
                        <td>
                            <div class="d-flex">
                                <p>
                                    {{ $clanPlayer->clan->short }}
                                </p>
                            </div>
                        </td>
                        <td>
                            <a href="/clans/{{ $ladder->abbreviation }}/edit/{{ $clanPlayer->clan_id }}/main" class="btn btn-primary btn-size-md">

                                @if ($clanPlayer->isMember())
                                View Clan - {{ $clanPlayer->clan->short }}
                                @else
                                Manage Clan - {{ $clanPlayer->clan->short }}
                                @endif
                            </a>
                        </td>
                        <td>
                            <strong>Role: {{ $clanPlayer->role }}</strong>
                            <br />
                            <strong class="username">Nick: {{ $clanPlayer->player->username }}</strong>
                        </td>
                        <td>
                            @foreach ($clanPlayer->clan->clanPlayers()->get() as $clanMember)
                            <strong class="username">{{ $clanMember->player->username }}</strong>
                            <br />
                            @endforeach
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-md" data-bs-toggle="modal" data-bs-target="#submitLeaveClan_{{ $clanPlayer->clan->id }}">
                                Leave Clan
                            </button>

                            <div class="modal fade" tabindex="-1" id="submitLeaveClan_{{ $clanPlayer->clan->id }}" tabIndex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Leave the clan?</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <form method="POST" action="/clans/{{ $ladder->abbreviation }}/leave/{{ $clanPlayer->clan->id }}/">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="id" value="{{ $clanPlayer->id }}">

                                                <p>Are you sure you want to leave the clan:
                                                    <strong>{{ $clanPlayer->clan->name }}</strong>?
                                                </p>

                                                <button type="submit" name="submit" value="leave" class="btn btn-danger">I want to
                                                    leave
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-md-12 mb-5 mt-5">
                @if ($ladder->clans_allowed)
                <h4><i class="bi bi-flag-fill icon-clan pe-3"></i> My Inactive Clans</h4>

                <div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Clan Short</th>
                                    <th scope="col">Full Clan Name</th>
                                    <th scope="col">Ex-Owner Name</th>
                                    <th scope="col">Activate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($myOldClans as $myOldClan)
                                <tr>
                                    <td>
                                        <div class="d-flex">
                                            <p>
                                                {{ $myOldClan->short }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <p>
                                                {{ $myOldClan->name }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <p>
                                                {{ $myOldClan->playerName }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" action="/clans/{{ $ladder->abbreviation }}/activate/{{ $myOldClan->id }}">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="id" value="{{ $myOldClan->id }}">
                                            <button type="submit" name="submit" value="activate" class="btn btn-primary btn-size-md activate">
                                                Activate Clan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="row mb-5">
            <div class="table-responsive">
                <h4><i class="bi bi-flag-fill icon-clan pe-3"></i> Your clan invites</h4>
                <p>If someone has invited you to a clan, they will appear here. </p>

                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Clan Short Name</th>
                            <th scope="col">Clan Full Name</th>
                            <th scope="col">Invited Username</th>
                            <th scope="col">All Members</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invitations as $invite)
                        <tr>
                            <td>
                                {{ $invite->clan->short }}
                            </td>
                            <td>
                                {{ $invite->clan->name }}
                            </td>
                            <td>
                                Has invited <strong class="username">{{ $invite->player->username }}</strong>
                            </td>
                            <td>
                                <form method="POST" action="/clans/{{ $ladder->abbreviation }}/invite/{{ $invite->clan_id }}/process">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="id" value="{{ $invite->id }}">

                                    <button type="submit" name="submit" value="accept" class="btn btn-primary btn-size-md accept">Accept
                                    </button>

                                    <button type="submit" name="submit" value="reject" class="btn btn-secondary btn-size-md reject">Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <p class="lead">
            You have to register and activate a username below for this ladder before you
            can join a clan
        </p>
        @endif
    </div>
</div>