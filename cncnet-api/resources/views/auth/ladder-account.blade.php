@extends('layouts.app')
@section('title', 'Ladder Account')
@section('body-class', 'ladder-account')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev($ladder->abbreviation))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev($ladder->abbreviation))

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $ladder->name }}</strong>
                    </h1>
                    <p class="lead">
                        Manage usernames for {{ $ladder->name }}.
                    </p>
                </div>
            </div>
            <div class="mini-breadcrumb d-none d-lg-flex">
                <div class="mini-breadcrumb-item">
                    <a href="/account">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        Manage Ladder Account
                    </a>
                </div>
                <div class="mini-breadcrumb-item">
                    <a href="">
                        <span class="material-symbols-outlined icon">
                            military_tech
                        </span>
                        Manage {{ $ladder->name }} Accounts
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/account">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        {{ $user->name }}'s Account
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="">
                        <span class="material-symbols-outlined icon pe-3">
                            military_tech
                        </span>
                        Manage {{ $ladder->name }} Accounts
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4">
        <div class="container">

            <div class="row">
                <div class="col-md-12">
                    @include('components.form-messages')
                </div>
            </div>

            <div class="row">
                @if (!$user->email_verified)
                    <div class="mt-4 mb-4">
                        <h2 class="text-center mb-5 mt-5 "><strong>Verify Your Email Address Before You Can Play!</strong></h2>
                        <div class="text-center">
                            <form class="form" method="POST" name="verify" action="/account/verify">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button type="submit" class="btn btn-primary">Click Here to Send a New Email</a>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    @if ($ladder->abbreviation == 'blitz')
                        <div class="mb-5">
                            <h2 class="mt-4">New player?</h2>
                            <p class="lead">
                                Join the YR Blitz Discord below and watch the videos below to get better!
                            </p>
                            <p class="lead">
                                There is also a training bootcamp hosted weekly, perfect for new or returning players!
                            </p>
                            <div class="useful-links d-md-flex mt-2">
                                <div class="me-3">
                                    <a href="{{ $ladder->qmLadderRules->ladder_discord }}" class="btn btn-secondary btn-size-md">
                                        <i class="bi bi-discord pe-2"></i> {{ $ladder->name }} Discord
                                    </a>
                                </div>

                                <div class="me-3">
                                    <a href="https://youtu.be/n_xWvNxO55c" class="btn btn-secondary btn-size-md" target="_blank">
                                        <i class="bi bi-youtube pe-2"></i> How-To Play Blitz Online
                                    </a>
                                </div>

                                <div>
                                    <a href="https://youtu.be/EPDCaucx5qA" class="btn btn-secondary btn-size-md" target="_blank">
                                        <i class="bi bi-youtube pe-2"></i> Tips & Tricks - New Blitz Players
                                    </a>
                                </div>
                            </div>
                        </div>
                    @elseif ($ladder->game == 'yr')
                        <div class="mb-5">
                            <h3 class="mt-4">New player?</h3>
                            <p class="lead">Join the Discord community for tips on how to play!</p>
                            <div class="me-3">
                                <a href="{{ $ladder->qmLadderRules->ladder_discord }}" class="btn btn-secondary btn-size-md">
                                    <i class="bi bi-discord pe-2"></i> {{ $ladder->name }} Discord
                                </a>
                            </div>
                        </div>
                    @endif

                @endif
            </div>

            <div class="row">
                @if ($ladder->clans_allowed)
                    <div class="col-md-12 mb-5 mt-5">
                        <h4><i class="bi bi-flag-fill icon-clan pe-3"></i> My Active Clans</h4>

                        @if ($activeHandles->count() > 0)
                            <p>
                                Please note: You are allowed to be in {{ $ladder->qmLadderRules->max_active_players }} clans at a time. If you have
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
                                                <tr>
                                                    <td>
                                                        <div class="d-flex">
                                                            <p>
                                                                {{ $clanPlayer->clan->short }}
                                                            </p>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <a href="/clans/{{ $ladder->abbreviation }}/edit/{{ $clanPlayer->clan_id }}/main"
                                                            class="btn btn-primary btn-size-md">
                                                            Mange your clan - {{ $clanPlayer->clan->short }}
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
                                                        <button type="button" class="btn btn-danger btn-md" data-bs-toggle="modal"
                                                            data-bs-target="#submitLeaveClan">Leave Clan</button>

                                                        <div class="modal fade" tabindex="-1" id="submitLeaveClan" tabIndex="-1" role="dialog">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Leave the clan?</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                    </div>

                                                                    <div class="modal-body">
                                                                        <form method="POST"
                                                                            action="/clans/{{ $ladder->abbreviation }}/leave/{{ $clanPlayer->clan->id }}/">
                                                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                                            <input type="hidden" name="id" value="{{ $clanPlayer->id }}">

                                                                            <p>Are you sure you want to leave the clan:
                                                                                <strong>{{ $clanPlayer->clan->name }}</strong>?
                                                                            </p>

                                                                            <button type="submit" name="submit" value="leave"
                                                                                class="btn btn-danger">I want to leave</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                    </td>
                                                </tr>
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
                                                                        <form method="POST"
                                                                            action="/clans/{{ $ladder->abbreviation }}/activate/{{ $myOldClan->id }}">
                                                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                                            <input type="hidden" name="id" value="{{ $myOldClan->id }}">
                                                                            <button type="submit" name="submit" value="activate"
                                                                                class="btn btn-primary btn-size-md activate">Activate Clan</button>
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
                                                            <form method="POST"
                                                                action="/clans/{{ $ladder->abbreviation }}/invite/{{ $invite->clan_id }}/process">
                                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                                <input type="hidden" name="id" value="{{ $invite->id }}">

                                                                <button type="submit" name="submit" value="accept"
                                                                    class="btn btn-primary btn-size-md accept">Accept</button>

                                                                <button type="submit" name="submit" value="reject"
                                                                    class="btn btn-secondary btn-size-md reject">Reject</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <p> You have to register an account for this ladder before you can join a clan</p>
                        @endif
                    </div>
                @endif

                <div class="col-md-12">
                    <div class="account-box">

                        <h3>How to play</h3>
                        <ol style="font-size: 1.2rem" class="mb-5 mt-3">
                            <li>Create a username.</li>
                            <li>Select <strong>"Play with username"</strong> so it will appear in your Ranked Match client.
                            </li>
                            <li>Usernames marked active below will be the nicknamme that appears in your Ranked Match client.</li>
                        </ol>

                        <p class="mb-5">
                            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLadderPlayer">
                                <i class="bi bi-person-plus pe-3"></i> Create new Username?
                            </a>
                        </p>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Username</th>
                                        <th scope="col">Username Active on Ladder</th>
                                        <th scope="col">Clan</th>
                                        <th scope="col"></th>
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
                                                @if ($player->clanPlayer && $player->clanPlayer->clan)
                                                    <strong class="fw-bold">
                                                        {{ $player->clanPlayer->clan->name }}
                                                    </strong>
                                                @else
                                                    <p>
                                                        None
                                                    </p>
                                                @endif
                                            </td>
                                            <td>
                                                <form id="username-status" class="form-inline" method="POST" action="username-status">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                                    <input type="hidden" name="username" value="{{ $player->username }}" />

                                                    @php $active = $activeHandles->where('player_id', $player->id)->count() > 0; @endphp
                                                    <button type="submit" class="btn {{ $active ? 'btn-primary' : 'btn-outline' }} btn-size-md">
                                                        {{ $active ? 'Stop playing with Username' : 'Play with Username' }}
                                                    </button>

                                                    <a href="/api/v1/ladder/{{ $player->ladder()->first()->abbreviation }}/player/{{ $player->username }}/webview"
                                                        target="_blank" class="btn btn-md me-3 btn-size-md">
                                                        OBS Stream Profile
                                                    </a>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <h4>About Usernames</h4>
                        <ul class="mt-4">
                            <li>Tiberian Sun and Clan Ladder players are now allowed up to 3 Active Players per month.</li>
                            <li>Red Alert &amp; Yuri's Revenge players are only allowed 1 nickname per month.</li>
                            <li>Users may not play on more than one account, in a month per ladder.</li>
                        </ul>

                        <h4 class="mt-4">OBS Stream Profiles</h4>
                        <p>
                            Overlay a webview of your stats in your stream using OBS.
                            <a href="/help/obs" target="_blank">Click here for OBS Stream Profiles Instructions</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="newLadderPlayer" tabIndex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create username for {{ $ladder->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form method="POST" action="username">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="ladder" value="{{ $ladder->id }}">
                        <p>
                            Usernames will be shown in the Ranked Match client and in-game.
                        </p>

                        <div class="mb-5">
                            <label for="username" class="form-label"><strong>Username</strong></label>
                            <input type="username" name="username" class="form-control border" id="username" placeholder="username">
                        </div>

                        <button type="submit" class="btn btn-primary">Create</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newLadderPlayer" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
                                        <p>Player usernames will be the name shown when you login to CnCNet clients and play games.
                                        </p>

                                        <div class="form-group">
                                            <label for="username">Username</label>
                                            <input type="text" name="username" class="form-control" id="username" placeholder="Username">
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

    <div class="modal fade" tabindex="-1" id="newClan">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create a new Clan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <form method="POST" action="/clans/{{ $ladder->abbreviation }}/edit/new">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="ladder_id" value="{{ $ladder->id }}">

                        <label>Select the player:</label>
                        @foreach ($activePlayersNotInAClan as $activePlayerNotInAClan)
                            <div>
                                <input type="radio" id="player_id_{{ $activePlayerNotInAClan->player->id }}" name="player_id"
                                    value="{{ $activePlayerNotInAClan->player->id }}">{{ $activePlayerNotInAClan->player->username }}</radio>
                            </div>
                        @endforeach

                        <p>
                            Short name will be the name appearing in the clients.
                        </p>
                        <p>
                            Full clan names will appear on the ladder.
                        </p>

                        <div class="form-group mb-2">
                            <label for="short">Short Name (6 Characters long)</label>
                            <input type="text" name="short" class="form-control border" id="short" placeholder="">
                        </div>

                        <div class="form-group mb-2">
                            <label for="name">Full Clan Name</label>
                            <input type="text" name="name" class="form-control border" id="name" placeholder="">
                        </div>

                        <button type="submit" class="btn btn-primary">Create Clan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newClan" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Create A New Clan!</h3>
                </div>
                <div class="modal-body clearfix">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                                <div class="account-box">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
