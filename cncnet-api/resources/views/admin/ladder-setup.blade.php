@extends('layouts.app')
@section('title', 'Ladder Setup')

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
                <p>Ladder Configuration</p>

                <a href="/admin/" class="btn btn-transparent btn-lg">
                    <i class="fa fa-chevron-left fa-lg fa-fw" aria-hidden="true"></i> Back to Admin
                </a>
            </div>
        </div>
    </div>
</div>
@endsection


@section('content')
<section class="general-texture">
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <h2>Match Ladder Rules</h2>
                    <p class="lead">Manage the configurables for Quick Match</p>

                    @include("components.form-messages")

                    <div class="row">
                        <div class="col-md-3">
                            <div class="player-box player-card" style="margin-bottom: 8px">

                                <p style="color: #fff" >Admins</p>
                                @foreach($ladder->admins()->where('admin', '=', true)->get() as $admin)
                                    <form method="POST" action="remove/admin" >
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" id="ladder_admin_id" name="ladder_admin_id" value="{{$admin->id}}" >
                                        {{$admin->user->name}} {{$admin->user->email}}
                                        @if($user->isGod())
                                            <button type="submit" id="remove_admin_{{$admin->id}}" style="margin: 0 0" class="btn btn-link btn-sm">Remove</button>
                                        @endif
                                    </form>
                                @endforeach
                                @if($user->isGod())
                                <form method="POST" action="add/admin">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="text" id="email" name="email" placeholder="newAdmin@email.com" />
                                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                                </form>
                                @endif
                            </div>

                            <div class="player-box player-card" style="margin-bottom: 8px">

                                <p style="color: #fff">Moderators</p>
                                @foreach($ladder->moderators()->where('moderator', '=', true)->where('admin', '=', false)->get() as $mod)
                                    <form method="POST" action="remove/moderator">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" id="ladder_admin_id" name="ladder_admin_id" value="{{$mod->id}}" >
                                        {{$mod->user->name}} {{$mod->user->email}}
                                        @if($user->isGod() || $user->isLadderAdmin($ladder))
                                        <button type="submit" id="remove_mod_{{$mod->id}}" style="margin: 0 0" class="btn btn-link btn-sm">Remove</button>
                                        @endif
                                    </form>
                                @endforeach
                                @if($user->isGod() || $user->isLadderAdmin($ladder))
                                <form method="POST" action="add/moderator">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="text" id="email" name="email" placeholder="newMod@email.com" />
                                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                                </form>
                                @endif
                            </div>

                            <div class="player-box player-card" style="margin-bottom: 8px">
                                <p style="color: #fff">Testers</p>
                                @foreach($ladder->testers()->where('tester', '=', true)->where('admin','=',false)->where('moderator', '=', false)->get() as $tester)
                                    <form method="POST" action="remove/tester">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" id="ladder_admin_id" name="ladder_admin_id" value="{{$tester->id}}" >
                                        {{$tester->user->name}} {{$tester->user->email}}
                                        @if($user->isGod() || $user->isLadderAdmin($ladder) || $user->isLadderMod($ladder))
                                        <button type="submit" id="remove_tester_{{$tester->id}}" style="margin: 0 0" class="btn btn-link btn-sm">Remove</button>
                                        @endif
                                    </form>
                                @endforeach
                                @if($user->isGod() || $user->isLadderAdmin($ladder) || $user->isLadderMod($ladder))
                                <form method="POST" action="add/tester">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="text" id="email" name="email" placeholder="newTester@email.com" />
                                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                                </form>
                                @endif
                            </div>

                            @if($user->isGod() || $user->isLadderAdmin($ladder))
                            <div class="player-box player-card" style="margin-bottom: 8px">
                                <p style="color: #fff">Alerts</p>
                                <select id="alertList"  class="form-control" size="4">
                                    @foreach($ladder->alerts as $alert)
                                        <option value="{{$alert->id}}">{{$alert->message}}</option>
                                    @endforeach
                                    <option value="new">&lt;new></option>
                                </select>
                                <button type="button" class="btn btn-primary btn-md" data-toggle="modal" data-target="#editAlert">Edit </button>
                            </div>
                            @endif

                        </div>

                        <div class="col-md-3">
                            <div class="player-box player-card" style="margin-bottom: 8px">
                                <p style="color: #fff">{{ $ladder->name }}</p>

                                <form method="POST" action="ladder">
                                    <input type="hidden"  name="_token" value="{{ csrf_token() }}">

                                    <div class="form-group">
                                        <label for="ladder_name">Ladder Name</label>
                                        <input id="ladder_name" name="name" type="text" class="form-control" value="{{$ladder->name}}" />
                                    </div>

                                    <div class="form-group col-md-6" style="padding-left: 0;">
                                        <label for="abbreviation">Abbreviation</label>
                                        <input id="ladder_abbreviation" name="abbreviation" type="text" class="form-control" value="{{$ladder->abbreviation}}" />
                                    </div>

                                    <div class="form-group col-md-6" style="padding-right: 0;">
                                        <label for="ladder_game">Game</label>
                                        <input id="ladder_game" name="game" type="text" class="form-control" value="{{$ladder->game}}" />
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-lg">Save</button>
                                </form>
                            </div>

                            <div class="player-box player-card" style="margin-bottom: 8px">
                                <p style="color: #fff">Add Side/Faction</p>

                                <form method="POST" action="addSide">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <div class="form-group col-md-4" style="padding-left: 0;">
                                        <label for="local_id">Index</label>
                                        <input id="local_id" name="local_id"  type="number" maxlength="3" size="3" class="form-control" style="font-size: 8px" value="{{ $ladder->sides()->max('local_id') + 1}}" />
                                    </div>
                                    <div class="form-group col-md-8" style="padding-right: 0;">
                                        <label for="name">Side Name</label>
                                        <input id="name" name="name" type="text" class="form-control" value="">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Add Side</button>
                                </form>

                            </div>
                            <div class="player-box player-card" style="margin-bottom: 8px">
                                <p style="color: #fff">Remove Side/Faction</p>

                                <form method="POST" action="remSide">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                    <div class="form-group">
                                        <label for="sides">Ladder Sides</label>

                                        <select id="sides" name="local_id" class="form-control">
                                            @foreach($ladder->sides()->orderBy('local_id', 'ASC')->get() as $side)
                                                <option value="{{ $side->local_id }}"> {{ $side->local_id }}, {{ $side->name }} </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-sm">Remove Side</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-3" >
                            <div class="player-box player-card">
                                <p style="color: #fff">Quick Match</p>

                                <form method="POST" action="rules">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="id" value="{{ $rule->id }}">

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_player_count">Player Count</label>
                                        <input id="{{ $rule->ladder_id }}_player_count" type="number" name="player_count" class="form-control" value="{{ $rule->player_count }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_map_vetoes">Map Vetoes</label>
                                        <input id="{{ $rule->ladder_id }}_map_vetoes" type="number" name="map_vetoes" class="form-control" value="{{ $rule->map_vetoes }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_difference">Matchup rating filter</label>
                                        <input id="{{ $rule->ladder_id }}_difference" type="number" name="max_difference" class="form-control" value="{{ $rule->max_difference }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_rating_per_second">Filter - rating per q-second</label>
                                        <input id="{{ $rule->ladder_id }}_rating_per_second" type="number" step="0.05" name="rating_per_second" class="form-control" value="{{ $rule->rating_per_second }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_point_difference">Matchup points filter</label>
                                        <input id="{{ $rule->ladder_id }}_point_difference" type="number" name="max_points_difference" class="form-control" value="{{ $rule->max_points_difference }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_points_per_second">Filter - points per q-second</label>
                                        <input id="{{ $rule->ladder_id }}_points_per_second" type="number" step="0.05" name="points_per_second" class="form-control" value="{{ $rule->points_per_second }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_bail_time">Bail Time</label>
                                        <input id="{{ $rule->ladder_id }}_bail_time" type="number" name="bail_time" class="form-control" value="{{ $rule->bail_time }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="show_map_preview">Show Map Preview in Client</label>
                                        <select id ="show_map_preview" name="show_map_preview" class="form-control">
                                            <option value="1" @if($rule->show_map_preview) selected @endif>Yes</option>
                                            <option value="0" @if(!$rule->show_map_preview) selected @endif>No</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->use_elo_points }}_use_elo_points">Use Elo for Points</label>
                                        <input id="{{ $rule->use_elo_points }}_use_elo_points" type="number" min="0" max="1" name="use_elo_points" class="form-control" value="{{ $rule->use_elo_points }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->wol_k }}_use_elo_points">WOL Points K Value</label>
                                        <input id="{{ $rule->wol_k }}_use_elo_points" type="number" min="0" name="wol_k" class="form-control" value="{{ $rule->wol_k }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_bail_fps">Bail FPS</label>
                                        <input id="{{ $rule->ladder_id }}_bail_fps" type="number" name="bail_fps" class="form-control" value="{{ $rule->bail_fps }}" />
                                    </div>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_tier2_rating">Tier 2 If Rating Below</label>
                                        <input id="{{ $rule->ladder_id }}_tier2_rating" type="number" name="tier2_rating" class="form-control" value="{{ $rule->tier2_rating }}" />
                                    </div>

                                    <?php $sides = \App\Side::where("ladder_id", "=", $rule->ladder_id)->orderby('local_id', 'ASC')->get(); ?>

                                    <div class="form-group">
                                        <label for="{{ $rule->ladder_id }}_sides">Random Sides</label>
                                        <input id="{{ $rule->ladder_id }}_sides" name="all_sides" type="text" class="form-control" value="{{$rule->all_sides}}" />
                                    </div>

                                    <div class="form-group">
                                        <?php $sideIdsAllowed = explode(',', $rule->allowed_sides); ?>
                                        <label>Allowed Sides</label>
                                        <div class="overflow-auto" style="height: 250px; overflow: auto; background: black;">
                                            @foreach($sides as $side)
                                                <div>
                                                    <input id="side_{{ $side->id }}" type="checkbox" name="allowed_sides[]" value="{{ $side->local_id }}" @if(in_array($side->local_id, $sideIdsAllowed)) checked @endif />
                                                    <label for="side_{{ $side->id }}">{{ $side->name }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-lg">Save</button>
                                </form>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="player-box player-card"  style="margin-bottom: 8px">
                                <form method="POST" action="mappool">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="ladder_id" value="{{ $rule->ladder_id }}" />
                                    <input type="hidden" name="qm_rules_id" value="{{ $rule->id }}" />
                                    <div class="form-group">
                                        <label for="mapPoolSelector"> Map Pool</label>
                                        <select id="mapPoolSelector" name="map_pool_id" class="form-control">
                                            @foreach($mapPools as $pool)
                                                @if($pool->id == $rule->map_pool_id)
                                                    <option value="{{ $pool->id }}" selected>{{ $pool->name }}</option>
                                                @else
                                                    <option value="{{ $pool->id }}">{{ $pool->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-md">Set</button>
                                    <a type="button" class="btn btn-primary btn-md" id="editMapPool" href="mappool/{{$rule->map_pool_id}}/edit">Edit</a>
                                </form>
                                <button type="button" class="btn btn-primary btn-md" data-toggle="modal" data-target="#newMapPool"> New </button>
                                <button type="button" class="btn btn-primary btn-md" id="doClone" data-toggle="modal" data-target="#cloneMapPool"> Clone </button>
                            </div>
                            <select id="optionList" size=12" class="form-control" style="margin-bottom: 8px">
                                @foreach($rule->spawnOptionValues as $sov)
                                    <option value="{{$sov->id}}">{{$sov->spawnOption->name->string}} : {{$sov->value->string}}</option>
                                @endforeach
                            </select>
                            @foreach($rule->spawnOptionValues as $sov)
                                @include('admin.spawn-option', [ 'sov' => $sov, 'rulesId' => $rule->id, 'qmMapId' => "", 'spawnOptions' => $spawnOptions,
                                                                                 "button" => "Update", "hidden" => true])
                            @endforeach
                            <?php $new_sov = new \App\SpawnOptionValue; $new_sov->id = "new"; $new_sov->value = ""; ?>
                            @include('admin.spawn-option', [ 'sov' => $new_sov, 'rulesId' => $rule->id, 'qmMapId' => "", 'spawnOptions' => $spawnOptions,
                                                                                 "button" => "Add", "hidden" => false])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="newMapPool" tabIndex="-1"  role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">New Map Pool</h3>
            </div>
            <div class="modal-body clearfix">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                            <form method="POST" action="mappool/new">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input type="hidden" name="ladder_id" value="{{ $rule->ladder_id }}" />
                                <input type="hidden" name="qm_rules_id" value="{{ $rule->id }}" />
                                <div class="form-group">
                                    <label for="map_pool_name"> Map Pool</label>
                                    <input type="text" id="map_pool_name" name="name" value="" class="form-control"/>
                                    <button type="submit" class="btn btn-primary btn-lg">Create</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cloneMapPool" tabIndex="-1"  role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Clone Map Pool</h3>
            </div>
            <div class="modal-body clearfix">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                            <form method="POST" action="mappool/clone">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input type="hidden" name="ladder_id" value="{{ $rule->ladder_id }}" />
                                <input type="hidden" name="qm_rules_id" value="{{ $rule->id }}" />
                                <input type="hidden" name="map_pool_id" value="" id="cloneMapPoolId">
                                <div class="form-group">
                                    <label for="map_pool_name"> Map Pool</label>
                                    <input type="text" id="map_pool_name" name="name" value="" class="form-control"/>
                                    <button type="submit" class="btn btn-primary btn-lg">Create</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editAlert" tabIndex="-1"  role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Edit Alert</h3>
            </div>
            <div class="modal-body clearfix">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card">
                            <form method="POST" action="alert">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input type="hidden" id="alertId" name="id" value="" />
                                <input type="hidden" name="ladder_id" value="{{$rule->ladder->id}}" />
                                <div class="form-group">
                                    <label for="alertText">Alert Text</label>
                                    <textarea id="alertText" name="message" class="form-control" rows="4" cols="50"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="alertDate">Expiration</label>
                                    <input type="text" id="alertDate" name="expires_at" class="form-control"></input>
                                </div>
                                <button type="submit" name="submit" value="update" class="btn btn-primary btn-md">Save</button>
                                <button type="submit" name="submit" value="delete" class="btn btn-danger btn-md">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script type="text/javascript">

     @if($user->isGod() || $user->isLadderAdmin($ladder))
     let ladderAlerts = {
         @foreach($ladder->alerts as $alert)
         "{{$alert->id}}": { "id": "{{$alert->id}}", "message": {!! json_encode($alert->message) !!}, "expires_at": "{{$alert->expires_at}}"},
         @endforeach
         "new": { "id":"new", "message": "", "expires_at": "{{ \Carbon\Carbon::now()->addMonth(1)->startOfMonth()}}" }
     };
     @else
     let ladderAlerts = { };
     @endif
     (function () {
         document.getElementById("mapPoolSelector").onchange = function ()
         {
             document.getElementById("editMapPool").href = "mappool/" + this.value + "/edit";
         };

     })();

     (function () {
         document.getElementById("doClone").onclick = function ()
         {
             let map_pool_id = document.getElementById("mapPoolSelector").value;
             document.getElementById("cloneMapPoolId").value = map_pool_id;
         }
     })();

     (function () {
         document.getElementById("optionList").onchange = function ()
         {
             let optList = document.querySelectorAll(".option");
             for (let i = 0; i < optList.length; i++)
             {
                 if (!optList[i].classList.contains('hidden') && !optList[i].classList.contains('new'))
                     optList[i].classList.add("hidden");
             }
             document.getElementById("option_" + this.value).classList.remove("hidden");
         };
     })();

     $(function() {
         $( "#alertDate" ).datepicker({ format: 'yyyy-mm-dd', startDate: "{{ \Carbon\Carbon::now()->addDay(1) }}" });
     });

     document.getElementById("alertList").onchange = function () {
         document.getElementById("alertId").value = ladderAlerts[this.value]["id"];
         document.getElementById("alertText").innerHTML = ladderAlerts[this.value]["message"];
         document.getElementById("alertText").value = ladderAlerts[this.value]["message"];
         document.getElementById("alertDate").value = ladderAlerts[this.value]["expires_at"];
     };

     (function () {
         if (document.getElementById("alertList").value === '')
             document.getElementById("alertList").selectedIndex = 0;

         window.addEventListener('load', function() { document.getElementById("alertList").onchange(); });
     })();
    </script>
@endsection
