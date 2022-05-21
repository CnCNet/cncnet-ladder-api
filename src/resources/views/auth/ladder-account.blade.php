@extends('layouts.app')
@section('title', 'Ladder Account')

@section('cover')
    /images/feature/feature-td.jpg
@endsection

@section('feature')
    <div class="feature-background sub-feature-background">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-8 col-md-offset-2">
                    <h1>
                        CnCNet Ladder Account
                    </h1>
                    <p class="text-uppercase">
                        Play. Compete. <strong>Conquer.</strong>
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
                            <h1>Hi {{ $user->name }} </h1>
                            <p class="lead">Manage everything to do with your CnCNet Ladder Account here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <section class="cncnet-features dark-texture">
        <div class="container">
            <div class="row">
                @if (!$user->email_verified)
                    <div class="col-md-12 tutorial">
                        <h2 class="text-center"><strong>Verify Your Email Address Before You Can Play!</strong></h2>
                        <div class="text-center">
                            <form class="form" method="POST" name="verify" action="/account/verify">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button type="submit" class="btn btn-link">Click Here to Send a New Code</a></button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include('components.form-messages')
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <?php $cards = \App\Models\Card::all(); ?>
                    @if ($ladder->clans_allowed)
                        <div class="clan-box">
                            <h2>Your Clan</h2>
                            @if ($activeHandles->count() > 0)
                                <p>
                                    You have to leave your clan before you can create a new one.<br />
                                </p>

                                <div class="clan-listings">
                                    @if ($primaryPlayer !== null)
                                        <div class="clan-listing new">
                                            <a href="#" class="new-clan" data-toggle="modal" data-target="#newClan">
                                                <i class="fa fa-plus" aria-hidden="true" style="font-size: 40px;"></i>
                                            </a>
                                            <div class="clan-status">
                                                <p> Create a new Clan owned by {{ $primaryPlayer->username }} </p>
                                            </div>
                                        </div>
                                    @endif
                                    @foreach ($clanPlayers as $clanPlayer)
                                        <div class="clan-listing active">
                                            <div class="clan-name clan-name-short">
                                                <a
                                                    href="/clans/{{ $ladder->abbreviation }}/edit/{{ $clanPlayer->clan_id }}/main">
                                                    <h2>{{ $clanPlayer->clan->short }}</h2>
                                                </a>
                                            </div>
                                            <div class="clan-name clan-name-long">
                                                <a
                                                    href="/clans/{{ $ladder->abbreviation }}/edit/{{ $clanPlayer->clan_id }}/main">
                                                    <h2>{{ $clanPlayer->clan->name }}</h2>
                                                </a>
                                            </div>
                                            <div class="text-center">
                                                <p>{{ $clanPlayer->role }}<strong
                                                        class="username">{{ $clanPlayer->player->username }}</strong>
                                                </p>
                                            </div>
                                            <div class="clan-username">
                                                <form method="POST"
                                                    action="/clans/{{ $ladder->abbreviation }}/leave/{{ $clanPlayer->clan->id }}/">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="hidden" name="id" value="{{ $clanPlayer->id }}">
                                                    <button type="submit" name="submit" value="leave"
                                                        class="btn btn-primary reject">Leave</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach ($invitations as $invite)
                                        <div class="clan-listing">
                                            <div class="clan-name short">
                                                <h2>{{ $invite->clan->short }}</h2>
                                            </div>
                                            <div class="clan-name long">
                                                <h2>{{ $invite->clan->name }}</h2>
                                            </div>
                                            <div>
                                                <p>Has invited <strong
                                                        class="username">{{ $invite->player->username }}</strong>
                                                </p>
                                            </div>
                                            <div class="clan-username">
                                                <form method="POST"
                                                    action="/clans/{{ $ladder->abbreviation }}/invite/{{ $invite->clan_id }}/process">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="hidden" name="id" value="{{ $invite->id }}">
                                                    <button type="submit" name="submit" value="accept"
                                                        class="btn btn-primary accept">Accept</button>
                                                    <button type="submit" name="submit" value="reject"
                                                        class="btn btn-primary reject">Reject</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p> You have to register an account for this ladder before you can join a clan</p>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="col-md-8 col-md-offset-2">
                    <?php $cards = \App\Models\Card::all(); ?>
                    <div class="account-box">
                        <h2>Registered Accounts</h2>
                        <p>
                            New rules everyone! You are only allowed:<br />
                        </p>
                        <ul>
                            <li>Tiberian Sun players are now allowed up to 3 nicknames per month.</li>
                            <li>Red Alert &amp; Yuri's Revenge players are only allowed 1 nickname per month.</li>
                            <li>One user account, having multiple accounts are not allowed. </li>
                        </ul>
                        <p>
                            To use your nickname, activate it and it will appear in your Quick Match client.
                        </p>

                        <div class="account-player-listings">
                            @if ($activeHandles->count() == 0)
                                <div class="player-listing new">
                                    <a href="#" class="new-username" data-toggle="modal" data-target="#newLadderPlayer">
                                        <i class="fa fa-plus" aria-hidden="true" style="font-size: 40px;"></i>
                                    </a>
                                    <div class="username-status">
                                        <p> Register an Account for this Ladder. </p>
                                    </div>
                                </div>
                            @endif

                            @foreach ($players as $player)
                                <div
                                    class="player-listing {{ $activeHandles->where('player_id', $player->id)->count() > 0 ? 'active' : '' }}">
                                    <div class="username">
                                        <i
                                            class="icon icon-game icon-{{ $player->ladder()->first()->abbreviation }}"></i>
                                        <div>
                                            {{ $player->username }}
                                        </div>
                                    </div>

                                    <div class="card">
                                        <p>Ladder player card</p>
                                        <form id="playerCard" class="form-inline" method="POST" action="card">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                            <input type="hidden" name="playerId" value="{{ $player->id }}" />

                                            <select class="form-control" name="cardId">
                                                @foreach ($cards as $card)
                                                    <option value="{{ $card->id }}"
                                                        @if ($card->id == $player->card_id) selected @endif>
                                                        {{ $card->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-submit">Save</button>
                                        </form>
                                        <div>
                                            <a href="/help/obs" target="_blank"><strong>New!</strong> OBS Stream
                                                Profiles (Instructions)
                                            </a>
                                            <a href="/api/v1/ladder/{{ $player->ladder()->first()->abbreviation }}/player/{{ $player->username }}/webview"
                                                target="_blank" class="btn btn-activate btn-secondary">
                                                OBS Stream Profile
                                            </a>
                                        </div>
                                    </div>


                                    <div class="username-status">
                                        <p>
                                            {{ $activeHandles->where('player_id', $player->id)->count() > 0 ? 'This username will appear in your Quick Match client. ' : 'Click Play to add this username to your Quick Match client' }}
                                        </p>
                                        <form id="username-status" class="form-inline" method="POST"
                                            action="username-status">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                            <input type="hidden" name="username" value="{{ $player->username }}" />

                                            <button type="submit" class="btn btn-activate">
                                                {{ $activeHandles->where('player_id', $player->id)->count() > 0 ? 'Deactivate' : 'Play with username' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="newLadderPlayer" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Create Ladder Account</h3>
                </div>
                <div class="modal-body clearfix">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                                <div class="account-box">
                                    <h2>Add a new username.</h2>
                                    <form method="POST" action="username">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="ladder" value="{{ $ladder->id }}">
                                        <p>Usernames will be the name shown when you login to CnCNet clients and play games.
                                        </p>

                                        <div class="form-group">
                                            <label for="username">Username</label>
                                            <input type="text" name="username" class="form-control" id="username"
                                                placeholder="Username">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Create username</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($primaryPlayer != null)
        <div class="modal fade" id="newClan" tabIndex="-1" role="dialog">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <h3 class="modal-title">Create A New Clan!</h3>
                    </div>
                    <div class="modal-body clearfix">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                                    <div class="account-box">
                                        <form method="POST" action="/clans/{{ $ladder->abbreviation }}/edit/new">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="ladder_id" value="{{ $ladder->id }}">
                                            <input type="hidden" name="player_id" value="{{ $primaryPlayer->id }}">

                                            <p>Usernames will be the name shown when you login to CnCNet clients and play
                                                games.</p>

                                            <div class="form-group">
                                                <label for="short">Short Name</label>
                                                <input type="text" name="short" class="form-control" id="short">
                                            </div>

                                            <div class="form-group">
                                                <label for="name">Full Clan Name</label>
                                                <input type="text" name="name" class="form-control" id="name"
                                                    placeholder="New Clan Name">
                                            </div>

                                            <button type="submit" class="btn btn-primary">Create Clan</button>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
