@extends('layouts.app')
@section('title', "Manage Clan")

@section('cover')
/images/feature/feature-index.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet <strong>Clan Ladders</strong>
                </h1>
                <p>
                   Play, Compete, <strong>Conquer!</strong>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section>
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center" style="padding-bottom: 40px;">
                        <h1>Manage Clan</h1>
                        <p class="lead">[{{ $clan->short }}] {{ $clan->name }}
                        @if($player->clanPlayer->isOwner())
                            <button class="btn btn-link inline-after-edit" data-toggle="modal" data-target="#renameClan"><i class="fa fa-edit"></i></button>
                        @endif
                        </p>
                        <p class="lead">{{  $clan->ladder->name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cncnet-features dark-texture">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                @include("components.form-messages")
            </div>
        </div>
        <div class="feature">
            <div class="row">
                <div class="col-md-5 clan-members-box">
                    <div class="clan-members-header">
                        <h3>Invitations</h3>
                        @if($player->clanPlayer->isOwner() || $player->clanPlayer->isManager())
                            <button class="btn btn-md btn-primary" data-toggle="modal" data-target="#sendInvitation">New Invite</button>
                        @endif
                    </div>
                    <div class="clan-members-body">
                        <table class="table clan-members-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Manager</th>
                                    <th>Sent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invitations as $invite)
                                    <tr>
                                        <td>
                                            @if($player->clanPlayer->isOwner() || $player->clanPlayer->isManager())
                                                <form action="/clans/{{ $ladder->abbreviation }}/invite/{{ $invite->clan_id }}/cancel" method="POST">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="hidden" name="id" value="{{ $invite->id }}">
                                                    <input type="hidden" name="player_id" value="{{ $player->id }}">
                                                    <button class="btn btn-link" type="submit" name="submit" data-toggle="tooltip" data-placement="top" title="Cancel Invitation">
                                                        <span class="fa fa-times fa-lg clan-danger"></span>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td>{{ $invite->player->username }}</td>
                                        <td>{{ $invite->author->username }}</td>
                                        <td>{{ $invite->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="clan-members-footer">
                        @if($invitations->count() > 10)
                            @if($player->clanPlayer->isOwner() || $player->clanPlayer->isManager())
                                <button class="btn btn-md btn-primary" data-toggle="modal" data-target="#sendInvitation">New Invite</button>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="col-md-4 clan-members-box">
                    <form method="POST" action="members">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="player_id" value="{{ $player->id }}">
                        <div class="clan-members-header">
                            <h3>Members</h3>
                            @if($player->clanPlayer->isOwner())
                                <div class="text-center"><button class="btn btn-primary" type="submit" name="submit" value="apply">Apply</button></div>
                            @endif
                        </div>
                        <div class="clan-members-body">
                            <table class="table clan-members-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($clan->clanPlayers as $cp)
                                        <tr>
                                            <td>{{ $cp->player->username }}</span></td>
                                            <td>
                                                @if($player->clanPlayer->isOwner())
                                                    <select class="form-control" name="role[{{ $cp->id }}]">
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->id }}" @if($cp->clan_role_id == $role->id) selected @endif>{{ $role->value }}</option>
                                                        @endforeach
                                                        <option value="kick">Kick</option>
                                                    </select>
                                                @else
                                                    {{ $cp->role }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="clan-members-footer">
                            @if($player->clanPlayer->isOwner())
                                @if($clan->clanPlayers->count() > 10)
                                    <div class="text-center"> <button class="btn btn-primary" type="submit" name="submit" value="apply">Apply</button></div>
                                @endif
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="renameClan" tabIndex="-1"  role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Rename Your Clan!</h3>
            </div>
            <div class="modal-body clearfix">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                            <div class="account-box">
                                <form method="POST" action="/clans/{{ $ladder->abbreviation }}/edit/{{ $clan->id }}" >
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}">
                                    <input type="hidden" name="player_id" value="{{ $player->id }}">

                                    <p>Usernames will be the name shown when you login to CnCNet clients and play games.</p>

                                    <div class="form-group">
                                        <label for="short">Short Name</label>
                                        <input type="text" name="short" class="form-control" id="short" value="{{ $clan->short }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="name">Full Clan Name</label>
                                        <input type="text" name="name" class="form-control" id="name" placeholder="New Clan Name" value="{{ $clan->name }}">
                                    </div>

                                    <button type="submit" class="btn btn-primary">Update Clan</button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sendInvitation" tabIndex="-1"  role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Invite A Player</h3>
            </div>
            <div class="modal-body clearfix">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                            <div class="account-box">
                                <form method="POST" action="/clans/{{ $ladder->abbreviation }}/invite/{{ $clan->id }}" >
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}">

                                    <div class="form-group">
                                        <label for="short">Player Name</label>
                                        <input type="text" name="playerName" class="form-control" id="playerName">
                                    </div>

                                    <button type="submit" class="btn btn-primary">Send</button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
