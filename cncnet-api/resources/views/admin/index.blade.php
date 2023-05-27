@extends('layouts.app')
@section('title', 'Ladder')

@section('feature-image', '/images/feature/feature-index.jpg')

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Admin</span>
                    </h1>
                </div>
            </div>

            <div class="mini-breadcrumb d-none d-lg-flex">
                <div class="mini-breadcrumb-item">
                    <a href="/" class="">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </div>
                <div class="mini-breadcrumb-item">
                    <a href="/admin" class="">
                        <span class="material-symbols-outlined">
                            admin_panel_settings
                        </span>
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
                    <a href="/admin">
                        <span class="material-symbols-outlined pe-3">
                            admin_panel_settings
                        </span>
                        Admin
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4">
        <div class="container">
            <div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="player-box player-card">
                            <h3>Users &amp; Clans</h3>
                            <p>Manage everything to do with Users</p>
                            <ul class="list-unstyled">
                                <li><a href="/admin/users" class="btn btn-md btn-secondary">User List</a></li>
                                <li><a href="/admin/players/ratings" class="btn btn-md btn-secondary mt-2">User Ratings</a></li>
                                <li><a href="/admin/clans" class="btn btn-md btn-secondary mt-2">Clan List</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="profile-link">
                            <div class="player-box player-card">
                                <h3>Games</h3>
                                <p>Manage maps, rules, and all things related to the ladder</p>

                                <ul class="list-styled">
                                    @foreach ($all_ladders as $ladder)
                                        <li>
                                            <a href="/admin/setup/{{ $ladder->id }}/edit">{{ $ladder->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($user->isGod())
                                    <button type="button" class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#newLadder">New
                                        Ladder</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="profile-link">
                            <div class="player-box player-card">
                                <h3>Game Schemas</h3>
                                <p>Manage Object Lists And Sides for games</p>

                                <ul class="list-styled">
                                    @foreach ($schemas as $schema)
                                        <li><a href="/admin/schema/{{ $schema->id }}">{{ $schema->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mt-4">
                        <div class="profile-link">
                            <div class="player-box player-card">
                                <h3>Canceled Matches</h3>

                                <label for="ladders_label">Ladder</label>

                                <select name="ladder_dropdown" id="ladder_dropdown" class="form-control border">
                                    @foreach ($ladders as $ladder)
                                        <option value="{{ $ladder->abbreviation }}"> {{ $ladder->abbreviation }} </option>
                                    @endforeach
                                </select>

                                <a id="canceledMatchesLink" href="/admin/canceledMatches/{{ $ladders[0]->abbreviation }}"
                                    class="btn btn-md btn-secondary mt-2">View</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mt-4">
                        <div class="profile-link">
                            <div class="player-box player-card">
                                <h3>Washed Games</h3>

                                <label for="ladders_label">Ladder</label>

                                <select name="ladder_dropdown2" id="ladder_dropdown2" class="form-control border">
                                    @foreach ($ladders as $ladder)
                                        <option value="{{ $ladder->abbreviation }}"> {{ $ladder->abbreviation }} </option>
                                    @endforeach
                                </select>

                                <a id="washedGamesLink" href="/admin/washedGames/{{ $ladders[0]->abbreviation }}"
                                    class="btn btn-md btn-secondary mt-2">View</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

@section('js')
    <script type="text/javascript">
        (function() {
            let ladderDropdown = document.getElementById("ladder_dropdown")
            let cancelLink = document.getElementById("canceledMatchesLink")

            ladderDropdown.onchange = function() {
                let value = ladderDropdown.value
                cancelLink.setAttribute("href", "canceledMatches/" + value);
            }
        })();

        (function() {
            let ladderDropdown2 = document.getElementById("ladder_dropdown2")
            let washLink = document.getElementById("washedGamesLink")

            ladderDropdown2.onchange = function() {
                let value = ladderDropdown2.value
                washLink.setAttribute("href", "washedGames/" + value);
            }
        })();
    </script>
@endsection

<div class="modal fadae" tabindex="-1" id="newLadder">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Ladder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="ladder/new">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="new">

                    <div class="form-group mb-2">
                        <label for="ladder_name">Ladder Name</label>
                        <input id="ladder_name" name="name" type="text" class="form-control border" value="" />
                    </div>

                    <div class="form-group col-md-6 mb-2" style="padding-left: 0;">
                        <label for="abbreviation">Abbreviation</label>
                        <input id="ladder_abbreviation" name="abbreviation" type="text" class="form-control border" value="" />
                    </div>

                    <div class="form-group col-md-6 mb-2" style="padding-right: 0;">
                        <label for="ladder_game">Game</label>
                        <input id="ladder_game" name="game" type="text" class="form-control border" value="" />
                    </div>

                    <div class="form-group mb-2">
                        <label for="gameObjectSchema">Game Object Schema</label>
                        <select name="game_object_schema_id" id="gameObjectSchema" class="form-control border">
                            @foreach ($schemas as $gos)
                                <option value="{{ $gos->id }}">{{ $gos->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label for="clansAllowed">Clans Allowed</label>
                        <select name="clans_allowed" id="clansAllowed" class="form-control border">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label for="privateLadder">Visibility</label>
                        <select name="private" id="privateLadder" class="form-control">
                            <option value="0">Public</option>
                            <option value="1">Private</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Create</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
