@extends('layouts.app')
@section('title', 'Ladder Setup')

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
    <div class="ladder-admin">
        <section class="mt-5 pt-5">
            <div class="container">
                <div class="feature">
                    <div class="row">
                        <div class="col-md-12">
                            <h2>Match Ladder Rules</h2>
                            <p class="lead">Manage the configurables for Quick Match</p>

                            @include('components.form-messages')

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="player-box player-card" style="margin-bottom: 8px">

                                        <p style="color: #fff">Admins</p>
                                        <div style="overflow-x: auto">
                                            @foreach ($ladder->admins()->where('admin', '=', true)->get() as $admin)
                                                <div style="white-space: nowrap;">
                                                    <form method="POST" action="remove/admin">
                                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                        <input type="hidden" id="ladder_admin_id" name="ladder_admin_id"
                                                               value="{{ $admin->id }}">
                                                        @if ($user->isGod())
                                                            <button type="submit" id="remove_admin_{{ $admin->id }}"
                                                                    style="margin: 0 0"
                                                                    class="btn btn-link btn-sm" data-toggle="tooltip"
                                                                    data-placement="top" title="Remove">
                                                                Remove
                                                            </button>
                                                        @endif
                                                        <span style="overflow: hidden;"
                                                              @if ($user->isGod()) data-toggle="tooltip"
                                                              data-placement="bottom"
                                                              title="{{ $admin->user->email }}" @endif>{{ $admin->user->name }}</span>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if ($user->isGod())
                                            <form method="POST" action="add/admin">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="text" id="email" name="email"
                                                       class="form-control border mt-2"
                                                       placeholder="newAXdmin@email.com"/>
                                                <button type="submit" class="btn btn-primary btn-md mt-2">Add</button>
                                            </form>
                                        @endif
                                    </div>

                                    <div class="player-box player-card" style="margin-bottom: 8px">

                                        <p style="color: #fff">Moderators</p>
                                        <div style="overflow-x: auto">
                                            @foreach ($ladder->moderators()->where('moderator', '=', true)->where('admin', '=', false)->get() as $mod)
                                                <div style="white-space: nowrap;">
                                                    <form method="POST" action="remove/moderator">
                                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                        <input type="hidden" id="ladder_admin_id" name="ladder_admin_id"
                                                               value="{{ $mod->id }}">
                                                        @if ($user->isGod() || $user->isLadderAdmin($ladder))
                                                            <button type="submit" id="remove_mod_{{ $mod->id }}"
                                                                    style="margin: 0 0"
                                                                    class="btn btn-link btn-sm" data-toggle="tooltip"
                                                                    data-placement="top" title="Remove">
                                                                Remove
                                                            </button>
                                                        @endif
                                                        <span style="overflow: hidden;"
                                                              @if ($user->isGod() || $user->isLadderAdmin($ladder)) data-toggle="tooltip"
                                                              data-placement="bottom"
                                                              title="{{ $mod->user->email }}" @endif>{{ $mod->user->name }}</span>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($user->isGod() || $user->isLadderAdmin($ladder))
                                            <form method="POST" action="add/moderator">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="text" id="email" name="email"
                                                       class="form-control mt-2 border"
                                                       placeholder="newMod@email.com"/>
                                                <button type="submit" class="btn btn-primary btn-md mt-2">Add</button>
                                            </form>
                                        @endif
                                    </div>

                                    <div class="player-box player-card" style="margin-bottom: 8px">
                                        <p style="color: #fff">Testers</p>
                                        <div style="overflow-x: auto">
                                            @foreach ($ladder->testers()->where('tester', '=', true)->where('admin', '=', false)->where('moderator', '=', false)->get() as $tester)
                                                <div style="white-space: nowrap;">
                                                    <form method="POST" action="remove/tester">
                                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                        <input type="hidden" id="ladder_admin_id" name="ladder_admin_id"
                                                               value="{{ $tester->id }}">
                                                        @if ($user->isGod() || $user->isLadderAdmin($ladder) || $user->isLadderMod($ladder))
                                                            <button type="submit" id="remove_tester_{{ $tester->id }}"
                                                                    style="margin: 0 0"
                                                                    class="btn btn-link btn-sm" data-toggle="tooltip"
                                                                    data-placement="top" title="Remove">
                                                                Remove
                                                            </button>
                                                        @endif
                                                        <span style="overflow: hidden;"
                                                              @if ($user->isGod() || $user->isLadderAdmin($ladder) || $user->isLadderMod($ladder)) data-toggle="tooltip"
                                                              data-placement="bottom"
                                                              title="{{ $tester->user->email }}" @endif>{{ $tester->user->name }}</span>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($user->isGod() || $user->isLadderAdmin($ladder) || $user->isLadderMod($ladder))
                                            <form method="POST" action="add/tester">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="text" id="email" name="email" class="form-control border"
                                                       placeholder="newTester@email.com"/>
                                                <button type="submit" class="btn btn-primary btn-md mt-2">Add</button>
                                            </form>
                                        @endif
                                    </div>

                                    @if ($user->isGod() || $user->isLadderAdmin($ladder))
                                        <div class="player-box player-card" style="margin-bottom: 8px">
                                            <p style="color: #fff">Alerts</p>
                                            <select id="alertList" class="form-control" size="4">
                                                @foreach ($ladder->alerts as $alert)
                                                    <option value="{{ $alert->id }}">{{ $alert->message }}</option>
                                                @endforeach
                                                <option value="new">&lt;new></option>
                                            </select>
                                            <button type="button" class="btn btn-primary btn-md mt-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editAlert">Edit
                                            </button>
                                        </div>
                                    @endif

                                </div>

                                <div class="col-md-3">
                                    <div class="player-box player-card" style="margin-bottom: 8px">
                                        <p style="color: #fff">{{ $ladder->name }}</p>

                                        <form method="POST" action="ladder">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                            <div class="form-group">
                                                <label for="ladder_name">Ladder Name</label>
                                                <input id="ladder_name" name="name" type="text" class="form-control"
                                                       value="{{ $ladder->name }}"/>
                                            </div>

                                            <div class="form-group col-md-6" style="padding-left: 0;">
                                                <label for="abbreviation">Abbreviation</label>
                                                <input id="ladder_abbreviation" name="abbreviation" type="text"
                                                       class="form-control"
                                                       value="{{ $ladder->abbreviation }}"/>
                                            </div>

                                            <div class="form-group col-md-6" style="padding-right: 0;">
                                                <label for="ladder_game">Game</label>
                                                <input id="ladder_game" name="game" type="text" class="form-control"
                                                       value="{{ $ladder->game }}"/>
                                            </div>

                                            <div class="form-group">
                                                <label for="gameObjectSchema">Game Object Schema</label>
                                                <select name="game_object_schema_id" id="gameObjectSchema"
                                                        class="form-control">
                                                    <option value=""></option>
                                                    @foreach ($objectSchemas as $gos)
                                                        <option value="{{ $gos->id }}"
                                                                @if ($ladder->game_object_schema_id == $gos->id) selected @endif>
                                                            {{ $gos->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="clansAllowed">Clans Allowed</label>
                                                <select name="clans_allowed" id="clansAllowed" class="form-control">
                                                    <option value="0" @if (!$ladder->clans_allowed) selected @endif>No
                                                    </option>
                                                    <option value="1" @if ($ladder->clans_allowed) selected @endif>Yes
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="privateLadder">Visibility</label>
                                                <select name="private" id="privateLadder" class="form-control">
                                                    <option value="0" @if (!$ladder->private) selected @endif>Public
                                                    </option>
                                                    <option value="1" @if ($ladder->private) selected @endif>Private
                                                    </option>
                                                </select>
                                            </div>

                                            <button type="submit" class="btn btn-primary btn-md">Save</button>
                                        </form>
                                    </div>

                                    <div class="player-box player-card" style="margin-bottom: 8px">
                                        <p style="color: #fff">Add Side/Faction</p>

                                        <form method="POST" action="addSide">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <div class="form-group col-md-4" style="padding-left: 0;">
                                                <label for="local_id">Index</label>
                                                <input id="local_id" name="local_id" type="number" maxlength="3"
                                                       size="3"
                                                       class="form-control border"
                                                       value="{{ $ladder->sides()->max('local_id') + 1 }}"/>
                                            </div>
                                            <div class="form-group col-md-8 mt-2" style="padding-right: 0;">
                                                <label for="name">Side Name</label>
                                                <input id="name" name="name" type="text" class="form-control border"
                                                       value="">
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-md mt-2">Add Side</button>
                                        </form>

                                    </div>
                                    <div class="player-box player-card" style="margin-bottom: 8px">
                                        <p style="color: #fff">Remove Side/Faction</p>

                                        <form method="POST" action="remSide">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                            <div class="form-group">
                                                <label for="sides">Ladder Sides</label>

                                                <select id="sides" name="local_id" class="form-control border">
                                                    @foreach ($ladder->sides()->orderBy('local_id', 'ASC')->get() as $side)
                                                        <option value="{{ $side->local_id }}"> {{ $side->local_id }}
                                                            , {{ $side->name }} </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-danger btn-md mt-2">Remove Side
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="player-box player-card" style="margin-bottom: 8px">
                                        <form method="POST" action="mappool">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                                            <input type="hidden" name="ladder_id" value="{{ $ladder->id }}"/>
                                            <div class="form-group mb-2">
                                                <label for="mapPoolSelector" style="color: #fff">Map Pool</label>
                                                <select id="mapPoolSelector" name="map_pool_id"
                                                        class="form-control border">
                                                    <option value=""></option>
                                                    @foreach ($mapPools as $pool)
                                                        @if ($pool->id == $ladder->map_pool_id)
                                                            <option value="{{ $pool->id }}"
                                                                    selected>{{ $pool->name }}</option>
                                                        @else
                                                            <option value="{{ $pool->id }}">{{ $pool->name }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-secondary btn-md">Set</button>
                                            <a type="button" class="btn btn-secondary btn-md" id="editMapPool"
                                               href="mappool/{{ $ladder->map_pool_id }}/edit">Edit</a>
                                        </form>
                                        <button type="button" class="btn btn-secondary btn-md" data-bs-toggle="modal"
                                                data-bs-target="#newMapPool">
                                            New
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-md" id="doClone"
                                                data-bs-toggle="modal"
                                                data-bs-target="#cloneMapPool"> Clone
                                        </button>
                                    </div>

                                    <select id="optionList" size=12" class="form-control" style="margin-bottom: 8px">
                                        @foreach ($ladder->spawnOptionValues as $sov)
                                            <option value="{{ $sov->id }}">{{ $sov->spawnOption->name->string }}
                                                : {{ $sov->value->string }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @foreach ($ladder->spawnOptionValues as $sov)
                                        @include('admin._spawn-option', [
                                            'sov' => $sov,
                                            'ladderId' => $ladder->id,
                                            'qmMapId' => '',
                                            'spawnOptions' => $spawnOptions,
                                            'button' => 'Update',
                                            'hidden' => true,
                                        ])
                                    @endforeach

                                    <?php
                                    $new_sov = new \App\Models\SpawnOptionValue();
                                    $new_sov->id = 'new';
                                    $new_sov->value = '';
                                    ?>
                                    @include('admin._spawn-option', [
                                        'sov' => $new_sov,
                                        'ladderId' => $ladder->id,
                                        'qmMapId' => '',
                                        'spawnOptions' => $spawnOptions,
                                        'button' => 'Add',
                                        'hidden' => false,
                                    ])
                                </div>

                                @if ($rule !== null)
                                    <div class="col-md-3">
                                        <div class="player-box player-card">
                                            <p style="color: #fff">Quick Match</p>

                                            <form method="POST" action="rules">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="id" value="{{ $rule->id }}">

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_player_count">Player
                                                        Count</label>
                                                    <input id="{{ $rule->ladder_id }}_player_count" type="number"
                                                           name="player_count"
                                                           class="form-control" value="{{ $rule->player_count }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->match_ai_after_seconds }}">Match AI after X
                                                        (Seconds)</label>
                                                    <input id="{{ $rule->match_ai_after_seconds }}" type="number"
                                                           name="match_ai_after_seconds"
                                                           class="form-control"
                                                           value="{{ $rule->match_ai_after_seconds }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_map_vetoes">Map Vetoes</label>
                                                    <input id="{{ $rule->ladder_id }}_map_vetoes" type="number"
                                                           name="map_vetoes"
                                                           class="form-control" value="{{ $rule->map_vetoes }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_difference">Matchup rating
                                                        filter</label>
                                                    <input id="{{ $rule->ladder_id }}_difference" type="number"
                                                           name="max_difference"
                                                           class="form-control" value="{{ $rule->max_difference }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_rating_per_second">Filter -
                                                        rating per q-second</label>
                                                    <input id="{{ $rule->ladder_id }}_rating_per_second" type="number"
                                                           step="0.05"
                                                           name="rating_per_second" class="form-control"
                                                           value="{{ $rule->rating_per_second }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_point_difference">Matchup points
                                                        filter</label>
                                                    <input id="{{ $rule->ladder_id }}_point_difference" type="number"
                                                           name="max_points_difference"
                                                           class="form-control"
                                                           value="{{ $rule->max_points_difference }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_points_per_second">Filter -
                                                        points per q-second</label>
                                                    <input id="{{ $rule->ladder_id }}_points_per_second" type="number"
                                                           step="0.05"
                                                           name="points_per_second" class="form-control"
                                                           value="{{ $rule->points_per_second }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_bail_time">Bail Time</label>
                                                    <input id="{{ $rule->ladder_id }}_bail_time" type="number"
                                                           name="bail_time" class="form-control"
                                                           value="{{ $rule->bail_time }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="show_map_preview">Show Map Preview in Client</label>
                                                    <select id="show_map_preview" name="show_map_preview"
                                                            class="form-control">
                                                        <option value="1" @if ($rule->show_map_preview) selected @endif>
                                                            Yes
                                                        </option>
                                                        <option value="0"
                                                                @if (!$rule->show_map_preview) selected @endif>No
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->use_elo_points }}_use_elo_points">Use Elo for
                                                        Points</label>
                                                    <input id="{{ $rule->use_elo_points }}_use_elo_points" type="number"
                                                           min="0"
                                                           max="1" name="use_elo_points" class="form-control"
                                                           value="{{ $rule->use_elo_points }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->wol_k }}_use_elo_points">WOL Points K
                                                        Value</label>
                                                    <input id="{{ $rule->wol_k }}_use_elo_points" type="number" min="0"
                                                           name="wol_k"
                                                           class="form-control" value="{{ $rule->wol_k }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_bail_fps">Bail FPS</label>
                                                    <input id="{{ $rule->ladder_id }}_bail_fps" type="number"
                                                           name="bail_fps" class="form-control"
                                                           value="{{ $rule->bail_fps }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_tier2_rating">Tier 2 If Rating
                                                        Below</label>
                                                    <input id="{{ $rule->ladder_id }}_tier2_rating" type="number"
                                                           name="tier2_rating"
                                                           class="form-control" value="{{ $rule->tier2_rating }}"/>
                                                </div>

                                                    <?php $sides = \App\Models\Side::where('ladder_id', '=', $rule->ladder_id)
                                                    ->orderby('local_id', 'ASC')
                                                    ->get(); ?>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_sides">Random Sides</label>
                                                    <input id="{{ $rule->ladder_id }}_sides" name="all_sides"
                                                           type="text" class="form-control"
                                                           value="{{ $rule->all_sides }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_reduce_map_repeats">Reduce Map
                                                        Repeats</label>
                                                    <input id="{{ $rule->ladder_id }}_reduce_map_repeats" min="0"
                                                           type="number"
                                                           name="reduce_map_repeats" class="form-control"
                                                           value="{{ $rule->reduce_map_repeats }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_point_filter_rank_threshold">Min
                                                        Rank for Pt Filter to be
                                                        Disabled</label>
                                                    <input id="{{ $rule->ladder_id }}_point_filter_rank_threshold"
                                                           min="0" type="number"
                                                           name="point_filter_rank_threshold" class="form-control"
                                                           value="{{ $rule->point_filter_rank_threshold }}"/>
                                                </div>

                                                <div class="form-group">
                                                        <?php $sideIdsAllowed = explode(',', $rule->allowed_sides); ?>
                                                    <label>Allowed Sides</label>
                                                    <div class="overflow-auto"
                                                         style="height: 250px; overflow: auto; background: black;">
                                                        @foreach ($sides as $side)
                                                            <div>
                                                                <input id="side_{{ $side->id }}" type="checkbox"
                                                                       name="allowed_sides[]"
                                                                       value="{{ $side->local_id }}"
                                                                       @if (in_array($side->local_id, $sideIdsAllowed)) checked @endif />
                                                                <label for="side_{{ $side->id }}">{{ $side->name }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_ladder_rules_message">Ladder
                                                        Rules Message</label>
                                                    <textarea id="{{ $rule->ladder_id }}_ladder_rules_message"
                                                              name="ladder_rules_message" cols="10" rows="20"
                                                              class="form-control"
                                                              value="{{ $rule->ladder_rules_message }}">{{ $rule->ladder_rules_message }}</textarea>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_ladder_discord">Ladder Discord
                                                        Url</label>
                                                    <input id="{{ $rule->ladder_id }}_ladder_discord" type="text"
                                                           name="ladder_discord"
                                                           class="form-control" value="{{ $rule->ladder_discord }}"/>
                                                </div>

                                                <div class="form-group">
                                                    <label for="{{ $rule->ladder_id }}_max_active_players">Max Active
                                                        Players Allowed per User</label>
                                                    <input id="{{ $rule->ladder_id }}_max_active_players" type="text"
                                                           name="max_active_players"
                                                           class="form-control"
                                                           value="{{ $rule->max_active_players }}"/>
                                                </div>

                                                <div class="form-group col-md-4">
                                                    <input type="checkbox"
                                                           id="{{ $rule->ladder_id }}_use_elo_map_picker"
                                                           name="use_elo_map_picker"
                                                           @if ($rule->use_ranked_map_picker) checked @endif />
                                                    <label for="{{ $rule->ladder_id }}_use_elo_map_picker">Use Elo Based
                                                        Map Picker</label>
                                                </div>

                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </form>

                                            <form method="POST" action="rules"
                                                  onsubmit="return confirm('This action will delete the quick match rules permanently.');"
                                                  class="mt-3">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="id" value="{{ $rule->id }}">

                                                <button type="submit" name="submit" value="delete"
                                                        class="btn btn-danger ">Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-3">
                                        <div class="player-box player-card">
                                            <form method="POST" action="rules">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="id" value="new">
                                                <button type="submit" class="btn btn-primary btn-md">Enable QuickMatch
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('admin._modal-new-map-pool')
        @include('admin._modal-clone-map-pool')
        @include('admin._modal-edit-alert')


    </div>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script type="text/javascript">
        @if ($user->isGod() || $user->isLadderAdmin($ladder))
        let ladderAlerts = {
            @foreach ($ladder->alerts as $alert)
            "{{ $alert->id }}": {
                "id": "{{ $alert->id }}",
                "message": {!! json_encode($alert->message) !!},
                "expires_at": "{{ $alert->expires_at }}"
            },
            @endforeach
            "new": {
                "id": "new",
                "message": "",
                "expires_at": "{{ \Carbon\Carbon::now()->addMonth(1)->startOfMonth() }}"
            }
        };
        @else
        let ladderAlerts = {};
        @endif
        (function() {
            document.getElementById("mapPoolSelector").onchange = function() {
                document.getElementById("editMapPool").href = "mappool/" + this.value + "/edit";
            };

        })();

        (function () {
            document.getElementById("doClone").onclick = function () {
                let map_pool_id = document.getElementById("mapPoolSelector").value;
                document.getElementById("cloneMapPoolId").value = map_pool_id;
            }
        })();

        (function () {
            document.getElementById("optionList").onchange = function () {
                let optList = document.querySelectorAll(".option");
                for (let i = 0; i < optList.length; i++) {
                    if (!optList[i].classList.contains('hidden') && !optList[i].classList.contains('new'))
                        optList[i].classList.add("hidden");
                }
                document.getElementById("option_" + this.value).classList.remove("hidden");
            };
        })();

        $(function () {
            $("#alertDate").datepicker({
                format: 'yyyy-mm-dd',
                startDate: "{{ \Carbon\Carbon::now()->addDay(1) }}"
            });
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

            window.addEventListener('load', function () {
                document.getElementById("alertList").onchange();
            });
        })();
    </script>
@endsection
