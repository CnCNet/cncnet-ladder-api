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
                    <strong class="fw-bold">CnCNet</strong>
                    <span>Ladder Account</span>
                </h1>
            </div>
        </div>
        <div class="mini-breadcrumb d-none d-lg-flex">
            <div class="mini-breadcrumb-item">
                <a href="/account">
                    <span class="material-symbols-outlined icon pe-3">
                        person
                    </span>
                    {{ $user->name }}'s Account
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
            <h2 class="mt-4">New player to play Blitz?</h2>
            <p>Join the Discord and watch the videos below to get started!</p>
            <section class="useful-links d-md-flex mt-4">
                <div class="me-3">
                    <a href="{{ $ladder->qmLadderRules->ladder_discord }}" class="btn btn-primary btn-size-md">
                        <i class="bi bi-discord pe-2"></i> {{ $ladder->name }} Discord
                    </a>
                </div>

                <div class="me-3">
                    <a href="https://youtu.be/n_xWvNxO55c" class="btn btn-primary btn-size-md" target="_blank">
                        <i class="bi bi-youtube pe-2"></i> How-To Play Blitz Online
                    </a>
                </div>

                <div>
                    <a href="https://youtu.be/EPDCaucx5qA" class="btn btn-primary btn-size-md" target="_blank">
                        <i class="bi bi-youtube pe-2"></i> Tips & Tricks - New Blitz Players
                    </a>
                </div>
            </section>
            @elseif ($ladder->game == 'yr')
            <h2 class="mt-4">New player?</h2>
            <p>Join the Discord community for tips on how to play!</p>
            <div class="me-3">
                <a href="{{ $ladder->qmLadderRules->ladder_discord }}" class="btn btn-primary btn-size-md">
                    <i class="bi bi-discord pe-2"></i> {{ $ladder->name }} Discord
                </a>
            </div>
            @endif

            @endif
        </div>

        <div class="row">
            <div class="col-md-12 mb-5 mt-5">
                @if ($ladder->clans_allowed)
                <h2>Your Clan</h2>

                @if ($activeHandles->count() > 0)
                <p>
                    Please note: You have to leave your clan before you can create a new one.<br />
                </p>

                <div class="clan-listings mb-5">
                    @if ($primaryPlayer !== null)
                    <a href="#" class="btn btn-primary btn-size-md" data-bs-toggle="modal" data-bs-target="#newClan">
                        Create a new Clan owned by {{ $primaryPlayer->username }}?
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
                                    <th scope="col">Role</th>
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
                                        <a href="/clans/{{ $ladder->abbreviation }}/edit/{{ $clanPlayer->clan_id }}/main" class="btn btn-primary btn-size-md">
                                            Manage Clan - {{ $clanPlayer->clan->short }}
                                        </a>
                                    </td>
                                    <td>
                                        <strong>Role: {{ $clanPlayer->role }}</strong>
                                        <strong class="username">{{ $clanPlayer->player->username }}</strong>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-md" data-bs-toggle="modal" data-bs-target="#submitLeaveClan">Leave Clan</button>

                                        <div class="modal fade" id="submitLeaveClan" tabIndex="-1" role="dialog">
                                            <div class="modal-dialog modal-md" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                    </div>
                                                    <div class="modal-body clearfix">
                                                        <div class="container-fluid">
                                                            <div class="row content">
                                                                <div class="col-md-12 player-box player-card list-inline">

                                                                    <div style="display: inline-block">
                                                                        <form method="POST" action="/clans/{{ $ladder->abbreviation }}/leave/{{ $clanPlayer->clan->id }}/">
                                                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                                            <input type="hidden" name="id" value="{{ $clanPlayer->id }}">
                                                                            <h3 class="modal-title">Are you sure you want to leave clan {{$clanPlayer->clan->name}}?</h3>
                                                                            <button type="submit" name="submit" value="leave" class="btn btn-danger">Leave Clan</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>



                    @foreach ($invitations as $invite)
                    <div class="clan-listing">
                        <div class="clan-name short">
                            <h2>{{ $invite->clan->short }}</h2>
                        </div>
                        <div class="clan-name long">
                            <h2>{{ $invite->clan->name }}</h2>
                        </div>
                        <div>
                            <p>
                                Has invited <strong class="username">{{ $invite->player->username }}</strong>
                            </p>
                        </div>
                        <div class="clan-username">
                            <form method="POST" action="/clans/{{ $ladder->abbreviation }}/invite/{{ $invite->clan_id }}/process">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="id" value="{{ $invite->id }}">
                                <button type="submit" name="submit" value="accept" class="btn btn-primary accept">Accept</button>
                                <button type="submit" name="submit" value="reject" class="btn btn-primary reject">Reject</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p> You have to register an account for this ladder before you can join a clan</p>
                @endif
                @endif
            </div>

            <div class="col-md-12">
                <div class="account-box">
                    <h3>Registered Accounts</h3>
                    <ul class="mt-4">
                        <li>Tiberian Sun players are now allowed up to 3 nicknames per month.</li>
                        <li>Red Alert &amp; Yuri's Revenge players are only allowed 1 nickname per month.</li>
                        <li>One user account, having multiple accounts are not allowed. </li>
                    </ul>
                    <p>
                        To use your nickname, activate it and it will appear in your Quick Match client.
                    </p>

                    <p>
                        <a href="#" class="btn btn-primary btn-size-md" data-bs-toggle="modal" data-bs-target="#newLadderPlayer">
                            <i class="bi bi-person-plus"></i> Add new Username?
                        </a>
                    </p>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Username</th>
                                    <th scope="col">Username Active</th>
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
                                    <td>{{ $activeHandles->where('player_id', $player->id)->count() > 0 ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        @if($player->clanPlayer && $player->clanPlayer->clan)
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
                                                {{ $active ? 'Deactivate' : 'Activate Username' }}
                                            </button>

                                            <a href="/api/v1/ladder/{{ $player->ladder()->first()->abbreviation }}/player/{{ $player->username }}/webview" target="_blank" class="btn btn-md me-3 btn-size-md">
                                                OBS Stream Profile
                                            </a>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h3>OBS Stream Profiles</h3>
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
                <h5 class="modal-title">Add a new username</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form method="POST" action="username">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="ladder" value="{{ $ladder->id }}">
                    <p>
                        Usernames will be the name shown when you login to CnCNet clients and play games.
                    </p>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="username" name="username" class="form-control border" id="username" placeholder="username">
                    </div>

                    <button type="submit" class="btn btn-primary">Create username</button>
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
                                    <p>Usernames will be the name shown when you login to CnCNet clients and play games.
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

@if ($primaryPlayer != null)
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
                    <input type="hidden" name="player_id" value="{{ $primaryPlayer->id }}">

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
@endif

@endsection