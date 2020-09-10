@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet Admin
                </h1>
                <p>Admin panel</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="light-texture game-detail supported-games">
    <div class="container">
        <div class="feature">

            <div class="row">
                <div class="col-md-4">
                    <a href="/admin/users" class="profile-link">
                        <div class="player-box player-card">
                            <h3>Users</h3>
                            <p>Manage everything to do with Users</p>
                            <ul class="list-unstyled">
                                <li><a href="/admin/users"> User List </a></li>
                            </ul>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <div class="profile-link">
                        <div class="player-box player-card">
                            <h3>Games</h3>
                            <p>Manage maps, rules, and all things related to the ladder</p>

                            <ul class="list-unstyled">
                                @foreach($all_ladders as $ladder)
                                    <li><a href="/admin/setup/{{$ladder->id}}/edit">{{$ladder->name}}</a></li>
                                @endforeach
                            </ul>
                            @if($user->isGod())
                                <button type="button" class="btn btn-primary btn-md" data-toggle="modal" data-target="#newLadder">New Ladder</button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="profile-link">
                        <div class="player-box player-card">
                            <h3>Game Schemas</h3>
                            <p>Manage Object Lists And Sides for games</p>

                            <ul class="list-unstyled">
                                @foreach($schemas as $schema)
                                    <li><a href="/admin/schema/{{ $schema->id }}">{{ $schema->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="newLadder" tabIndex="-1"  role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">New Ladder</h3>
            </div>
            <div class="modal-body clearfix">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                            <form method="POST" action="ladder/new">
                                <input type="hidden"  name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="id" value="new">

                                <div class="form-group">
                                    <label for="ladder_name">Ladder Name</label>
                                    <input id="ladder_name" name="name" type="text" class="form-control" value="" />
                                </div>

                                <div class="form-group col-md-6" style="padding-left: 0;">
                                    <label for="abbreviation">Abbreviation</label>
                                    <input id="ladder_abbreviation" name="abbreviation" type="text" class="form-control" value="" />
                                </div>

                                <div class="form-group col-md-6" style="padding-right: 0;">
                                    <label for="ladder_game">Game</label>
                                    <input id="ladder_game" name="game" type="text" class="form-control" value="" />
                                </div>

                                <div class="form-group">
                                    <label for="gameObjectSchema">Game Object Schema</label>
                                    <select name="game_object_schema_id" id="gameObjectSchema" class="form-control">
                                        @foreach ($schemas as $gos)
                                            <option value="{{ $gos->id }}">{{ $gos->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="clansAllowed">Clans Allowed</label>
                                    <select name="clans_allowed" id="clansAllowed" class="form-control">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="privateLadder">Visibility</label>
                                    <select name="private" id="privateLadder" class="form-control">
                                        <option value="0">Public</option>
                                        <option value="1">Private</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg">Create</button>
                        </div>
                            </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
