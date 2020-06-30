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
                    <p>Map Pool Configuration</p>

                    <a href="{{ $ladderUrl }}" class="btn btn-transparent btn-lg">
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
                        @include("components.form-messages")
                    </div>
                    <div class="col-md-12">
                        <h2>{{ $mapPool->name }}</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editMapPool"> Edit </button>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="rempoolmap">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                            <select id="mapPoolSelector" name="map_id" size="12" class="form-control">
                                <?php $lastmap=null ?>
                                @foreach($maps as $map)
                                    <option value="{{ $map->id }}" {{ old('map_selected') == $map->id ? "selected" : "" }}>
                                        {{ $map->admin_description }}
                                    </option>
                                    <?php $lastmap = $map ?>
                                @endforeach
                                @if ($lastmap !== null)
                                    <?php $new_map = $lastmap->replicate();
                                    $new_map->bit_id++;
                                    $new_map->id = 'new';
                                    $new_map->description = "Copy of " . $lastmap->description;
                                    $new_map->admin_description = "Copy of ". $lastmap->admin_description;
                                    ?>
                                @else
                                    <?php $new_map = new \App\QmMap;
                                    $new_map->id = "new";
                                    $new_map->map_pool_id = $mapPool->id;
                                    $new_map->valid = 1;
                                    ?>
                                @endif
                                <?php $maps->push($new_map); ?>
                                <option value="new">&lt;New Map></option>
                            </select>
                            <button type="submit" class="btn btn-danger">Remove Map</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="player-card" id="thumbnailContainer">
                            <img class="img-fluid img-thumbnail" id="mapThumbnail" src="">
                        </div>
                    </div>
                    <div class="col-md-12">
                        @foreach($maps as $map)
                            <div class="map hidden" id="map_{{$map->id}}">
                                <form method="POST" action="upmap" class="map" id="qmapu_{{ $map->map_pool_id }}_{{ $map->id }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="id" value="{{ $map->id }}" />
                                    <button type="submit" class="btn btn-primary">Move Up</button>
                                </form>

                                <form method="POST" action="downmap" class="map" id="qmapd_{{ $map->map_pool_id  }}_{{ $map->id }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="id" value="{{ $map->id }}" />
                                    <button type="submit" class="btn btn-primary">Move Down</button>
                                </form>

                                <form method="POST" action="edit" class="map" id="qmap_{{ $map->map_pool_id }}_{{ $map->id }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="id" value="{{ $map->id }}" />
                                    <input type="hidden" name="map_pool_id" value="{{ $map->map_pool_id}}" />
                                    <input type="hidden" name="bit_idx" value="{{ $map->bit_idx }}" />
                                    <input type="hidden" name="valid" value="{{ $map->valid }}" />

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_description"> Description </label>
                                        <input type="text" id="{{ $map->id }}_description" name="description" value="{{ $map->description }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_admin_description"> Admin Description </label>
                                        <input type="text" id="{{ $map->id }}_admin_description" name="admin_description" value="{{ $map->admin_description }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_spawn_order">spawn_order</label>
                                        <input type="text" id="{{ $map->id }}_spawn_order" name="spawn_order" value="{{ $map->spawn_order }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_team1_spawn_order">team1_spawn_order</label>
                                        <input type="text" id="{{ $map->id }}_team1_spawn_order" name="team1_spawn_order" value="{{ $map->team1_spawn_order }} " class="form-control"  />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_team2_spawn_order">team2_spawn_order</label>
                                        <input type="text" id="{{ $map->id }}_team2_spawn_order" name="team2_spawn_order" value="{{ $map->team2_spawn_order }}" class="form-control"  />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_game_mode">game_mode</label>
                                        <input type="text" id="{{ $map->id }}_game_mode" name="game_mode" value="{{ $map->game_mode }}" class="form-control"  />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_ai_player_count"> ai_player_count </label>
                                        <input id="{{ $map->id }}_ai_player_count" name="ai_player_count" type="number" value="{{ $map->ai_player_count }}" class="form-control"  />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_map"> map </label>
                                        <select id="{{ $map->id }}_map" name="map_id" class="form-control map-selector"></select>
                                        <button type="button" class="btn btn-primary btn-md" id="editMaps" data-toggle="modal" data-target="#editLadderMap"> Edit/New </button>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $rule->ladder_id }}_allowed_sides">Allowed Sides</label>

                                        <?php $sideIdsAllowed = array_map('intval', explode(',', $map->allowed_sides)); ?>
                                        <select id="{{ $map->id }}_allowed_sides" name="allowed_sides[]" class="form-control" multiple>
                                            @foreach($sides as $side)
                                                <option value="{{ $side->local_id }}" @if(in_array($side->local_id, $sideIdsAllowed)) selected @endif > {{ $side->name }} </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-md-12" >
                                        <button type="submit" class="btn btn-primary btn-lg">Save</button>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_speed"> speed </label>
                                        <select id="{{ $map->id }}_speed" name="speed" class="form-control" >
                                            @foreach(range(0,6) as $speed)
                                                <option value="{{ $speed }}" @if($map->speed == $speed) selected @endif > {{ $speed }} </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_credits"> credits </label>
                                        <input id="{{ $map->id }}_credits" name="credits" type="number" value="{{ $map->credits }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_units"> units </label>
                                        <input id="{{ $map->id }}_units" name="units" type="number" value="{{ $map->units }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_tech"> tech </label>
                                        <input id="{{ $map->id }}_tech" name="tech" type="number" value="{{ $map->tech }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_firestorm"> firestorm </label>
                                        <select id="{{ $map->id }}_firestorm" name="firestorm" class="form-control" >
                                            <option value="Null" @if(is_null($map->firestorm)) selected @endif >Null</option>
                                                <option value="No" @if($map->firestorm === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->firestorm === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_ra2_mode"> ra2_mode </label>
                                        <select id="{{ $map->id }}_ra2_mode" name="ra2_mode" class="form-control" >
                                            <option value="Null" @if(is_null($map->ra2_mode)) selected @endif>Null</option>
                                                <option value="No" @if($map->ra2_mode === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->ra2_mode === 1) selected @endif>Yes</option>
                                                        <option value="Random" @if($map->ra2_mode === -1) selected @endif>Random</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_short_game"> short_game </label>
                                        <select id="{{ $map->id }}_short_game" name="short_game" class="form-control" >
                                            <option value="Null" @if(is_null($map->short_game)) selected @endif>Null</option>
                                                <option value="No" @if($map->short_game === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->short_game === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_multi_eng"> multi_eng </label>
                                        <select id="{{ $map->id }}_multi_eng" name="multi_eng" class="form-control" >
                                            <option value="Null" @if(is_null($map->multi_eng)) selected @endif>Null</option>
                                                <option value="No" @if($map->multi_eng === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->multi_eng === 1) selected @endif>Yes</option>
                                                        <option value="Random" @if($map->multi_eng === -1) selected @endif>Random</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_dog_kill"> No Dog Eat Engineer </label>
                                        <select id="{{ $map->id }}_dog_kill" name="dog_kill" class="form-control" >
                                            <option value="Null" @if(is_null($map->dog_kill)) selected @endif>Null</option>
                                                <option value="No" @if($map->dog_kill === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->dog_kill === 1) selected @endif>Yes</option>
                                                        <option value="Random" @if($map->dog_kill === -1) selected @endif>Random</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_redeploy"> redeploy </label>
                                        <select id="{{ $map->id }}_redeploy" name="redeploy" class="form-control" >
                                            <option value="Null" @if(is_null($map->redeploy)) selected @endif>Null</option>
                                                <option value="No" @if($map->redeploy === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->redeploy === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_crates"> crates </label>
                                        <select id="{{ $map->id }}_crates" name="crates" class="form-control" >
                                            <option value="Null" @if(is_null($map->crates)) selected @endif>Null</option>
                                                <option value="No" @if($map->crates === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->crates === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_bases"> bases </label>
                                        <select id="{{ $map->id }}_bases" name="bases" class="form-control" >
                                            <option value="Null" @if(is_null($map->bases)) selected @endif>Null</option>
                                                <option value="No" @if($map->bases === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->bases === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_harv_truce"> harvester_truce </label>
                                        <select id="{{ $map->id }}_harv_truce" name="harv_truce" class="form-control" >
                                            <option value="Null" @if(is_null($map->harv_truce)) selected @endif>Null</option>
                                                <option value="No" @if($map->harv_truce === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->harv_truce === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_allies"> allies </label>
                                        <select id="{{ $map->id }}_allies" name="allies" class="form-control" >
                                            <option value="Null" @if(is_null($map->allies)) selected @endif>Null</option>
                                                <option value="No" @if($map->allies === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->allies === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_bridges"> bridge_destroy </label>
                                        <select id="{{ $map->id }}_bridges" name="bridges" class="form-control" >
                                            <option value="Null" @if(is_null($map->bridges)) selected @endif>Null</option>
                                                <option value="No" @if($map->bridges === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->bridges === 1) selected @endif>Yes</option>
                                                        <option value="Random" @if($map->bridges === -1) selected @endif>Random</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_fog"> fog </label>
                                        <select id="{{ $map->id }}_fog" name="fog" class="form-control" >
                                            <option value="Null" @if(is_null($map->fog)) selected @endif>Null</option>
                                                <option value="No" @if($map->fog === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->fog === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_build_ally"> build_ally </label>
                                        <select id="{{ $map->id }}_build_ally" name="build_ally" class="form-control" >
                                            <option value="Null" @if(is_null($map->build_ally)) selected @endif>Null</option>
                                                <option value="No" @if($map->build_ally === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->build_ally === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_multi_factory"> multi_factory </label>
                                        <select id="{{ $map->id }}_multi_factory" name="multi_factory" class="form-control" >
                                            <option value="Null" @if(is_null($map->multi_factory)) selected @endif>Null</option>
                                                <option value="No" @if($map->multi_factory === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->multi_factory === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_aimable_sams"> aimable_sams </label>
                                        <select id="{{ $map->id }}_aimable_sams" name="aimable_sams" class="form-control" >
                                            <option value="Null" @if(is_null($map->aimable_sams)) selected @endif>Null</option>
                                                <option value="No" @if($map->aimable_sams === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->aimable_sams === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_attack_neutral"> attack_neutral </label>
                                        <select id="{{ $map->id }}_attack_neutral" name="attack_neutral" class="form-control" >
                                            <option value="Null" @if(is_null($map->attack_neutral)) selected @endif>Null</option>
                                                <option value="No" @if($map->attack_neutral === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->attack_neutral === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_supers"> supers </label>
                                        <select id="{{ $map->id }}_supers" name="supers" class="form-control" >
                                            <option value="Null" @if(is_null($map->supers)) selected @endif>Null</option>
                                                <option value="No" @if($map->supers === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->supers === 1) selected @endif>Yes</option>
                                                        <option value="Random" @if($map->supers === -1) selected @endif>Random</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_spawn_preview"> spawn_preview </label>
                                        <select id="{{ $map->id }}_spawn_preview" name="spawn_preview" class="form-control" >
                                            <option value="Null" @if(is_null($map->spawn_preview)) selected @endif>Null</option>
                                                <option value="No" @if($map->spawn_preview === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->spawn_preview === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>


                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_fix_ai_ally"> fix_ai_ally </label>
                                        <select id="{{ $map->id }}_fix_ai_ally" name="fix_ai_ally" class="form-control" >
                                            <option value="Null" @if(is_null($map->fix_ai_ally)) selected @endif>Null</option>
                                                <option value="No" @if($map->fix_ai_ally === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->fix_ai_ally === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_ally_reveal"> ally_reveal </label>
                                        <select id="{{ $map->id }}_ally_reveal" name="ally_reveal" class="form-control" >
                                            <option value="Null" @if(is_null($map->ally_reveal)) selected @endif>Null</option>
                                                <option value="No" @if($map->ally_reveal === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->ally_reveal === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_am_fast_build"> am_fast_build </label>
                                        <select id="{{ $map->id }}_am_fast_build" name="am_fast_build" class="form-control" >
                                            <option value="Null" @if(is_null($map->am_fast_build)) selected @endif>Null</option>
                                                <option value="No" @if($map->am_fast_build === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->am_fast_build === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_parabombs"> parabombs </label>
                                        <select id="{{ $map->id }}_parabombs" name="parabombs" class="form-control" >
                                            <option value="Null" @if(is_null($map->parabombs)) selected @endif>Null</option>
                                                <option value="No" @if($map->parabombs === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->parabombs === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_fix_formation_speed"> fix_formation_speed </label>
                                        <select id="{{ $map->id }}_fix_formation_speed" name="fix_formation_speed" class="form-control" >
                                            <option value="Null" @if(is_null($map->fix_formation_speed)) selected @endif>Null</option>
                                                <option value="No" @if($map->fix_formation_speed === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->fix_formation_speed === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_fix_magic_build"> fix_magic_build </label>
                                        <select id="{{ $map->id }}_fix_magic_build" name="fix_magic_build" class="form-control" >
                                            <option value="Null" @if(is_null($map->fix_magic_build)) selected @endif>Null</option>
                                                <option value="No" @if($map->fix_magic_build === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->fix_magic_build === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_fix_range_exploit"> fix_range_exploit </label>
                                        <select id="{{ $map->id }}_fix_range_exploit" name="fix_range_exploit" class="form-control" >
                                            <option value="Null" @if(is_null($map->fix_range_exploit)) selected @endif>Null</option>
                                                <option value="No" @if($map->fix_range_exploit === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->fix_range_exploit === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_super_tesla_fix"> super_tesla_fix </label>
                                        <select id="{{ $map->id }}_super_tesla_fix" name="super_tesla_fix" class="form-control" >
                                            <option value="Null" @if(is_null($map->super_tesla_fix)) selected @endif>Null</option>
                                                <option value="No" @if($map->super_tesla_fix === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->super_tesla_fix === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_forced_alliances"> forced_alliances </label>
                                        <select id="{{ $map->id }}_forced_alliances" name="forced_alliances" class="form-control" >
                                            <option value="Null" @if(is_null($map->forced_alliances)) selected @endif>Null</option>
                                                <option value="No" @if($map->forced_alliances === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->forced_alliances === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_tech_center_fix"> tech_center_fix </label>
                                        <select id="{{ $map->id }}_tech_center_fix" name="tech_center_fix" class="form-control" >
                                            <option value="Null" @if(is_null($map->tech_center_fix)) selected @endif>Null</option>
                                                <option value="No" @if($map->tech_center_fix === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->tech_center_fix === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_no_screen_shake"> no_screen_shake </label>
                                        <select id="{{ $map->id }}_no_screen_shake" name="no_screen_shake" class="form-control" >
                                            <option value="Null" @if(is_null($map->no_screen_shake)) selected @endif>Null</option>
                                                <option value="No" @if($map->no_screen_shake === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->no_screen_shake === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_no_tesla_delay"> no_tesla_delay </label>
                                        <select id="{{ $map->id }}_no_tesla_delay" name="no_tesla_delay" class="form-control" >
                                            <option value="Null" @if(is_null($map->no_tesla_delay)) selected @endif>Null</option>
                                                <option value="No" @if($map->no_tesla_delay === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->no_tesla_delay === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <Label for="{{ $map->id }}_dead_player_radar"> dead_player_radar </label>
                                        <select id="{{ $map->id }}_dead_player_radar" name="dead_player_radar" class="form-control" >
                                            <option value="Null" @if(is_null($map->dead_player_radar)) selected @endif>Null</option>
                                                <option value="No" @if($map->dead_player_radar === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->dead_player_radar === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_capture_flag"> capture_flag </label>
                                        <select id="{{ $map->id }}_capture_flag" name="capture_flag" class="form-control" >
                                            <option value="Null" @if(is_null($map->capture_flag)) selected @endif>Null</option>
                                                <option value="No" @if($map->capture_flag === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->capture_flag === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_slow_unit_build"> slow_unit_build </label>
                                        <select id="{{ $map->id }}_slow_unit_build" name="slow_unit_build" class="form-control" >
                                            <option value="Null" @if(is_null($map->slow_unit_build)) selected @endif>Null</option>
                                                <option value="No" @if($map->slow_unit_build === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->slow_unit_build === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_shroud_regrows"> shroud_regrows </label>
                                        <select id="{{ $map->id }}_shroud_regrows" name="shroud_regrows" class="form-control" >
                                            <option value="Null" @if(is_null($map->shroud_regrows)) selected @endif>Null</option>
                                                <option value="No" @if($map->shroud_regrows === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->shroud_regrows === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_ore_regenerates"> ore_regenerates </label>
                                        <select id="{{ $map->id }}_ore_regenerates" name="ore_regenerates" class="form-control">
                                            <option value="Null" @if(is_null($map->ore_regenerates)) selected @endif>Null</option>
                                                <option value="No" @if($map->ore_regenerates === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->ore_regenerates === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="{{ $map->id }}_aftermath"> aftermath </label>
                                        <select id="{{ $map->id }}_aftermath" name="aftermath" class="form-control">
                                            <option value="Null" @if(is_null($map->aftermath)) selected @endif>Null</option>
                                                <option value="No" @if($map->aftermath === 0) selected @endif>No</option>
                                                    <option value="Yes" @if($map->aftermath === 1) selected @endif>Yes</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <button type="submit" class="btn btn-primary btn-lg">Save</button>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

<div class="modal fade" id="editMapPool" tabIndex="-1"  role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Edit Map Pool</h3>
            </div>
            <div class="modal-body clearfix">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                            <form method="POST" action="remove" onsubmit="return confirm('This action will delete the whole map pool and all maps permanently.');">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <button type="submit" class="btn btn-danger btn-md">Delete</button>
                            </form>
                            <form method="POST" action="rename">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <div class="form-group">
                                    <label for="map_pool_name"> Pool Name </label>
                                    <input type="text" id="map_pool_name" name="name" size="100" value="{{ $mapPool->name }}" class="form-control" />
                                </div>
                                <div class="form-group" >
                                    <button type="submit" class="btn btn-primary btn-md">Change</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="editLadderMap" tabIndex="-1"  role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Edit Map Hashes</h3>
            </div>
            <div class="modal-body clearfix">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                            <form method="POST" action="remmap">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input type="hidden" name="ladder_id" value="{{ $rule->ladder_id }}" />
                                <p style="color: #fff" >Map Hashes</p>

                                <select id="ladderMapSelector" name="map_id" size="6" class="form-control map_pool">
                                    @foreach($ladderMaps as $map)
                                        <option value="{{ $map->id }}"> {{ $map->name }} </option>
                                    @endforeach
                                    <option value="new">&lt;new></option>
                                </select>
                            </form>
                            <form method="POST" action="../../editmap" class="map" id="ladderMapEdit" enctype="multipart/form-data">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input type="hidden" name="ladder_id" value="{{ $rule->ladder_id }}" />
                                <input type="hidden" id="ladderMapId" name="map_id" value="new" />
                                <input type="hidden" id="mapSelected" name="map_selected" value="" />

                                <div class="form-group">
                                    <label for="text_map_new_name"> Name </label>
                                    <input type="text" id="ladderMapName" name="name" value="" class="form-control" />
                                </div>

                                <div class="form-group">
                                    <label for="text_map_new_hash"> SHA1 Hash </label>
                                    <textarea id="ladderMapHash" rows=1 name="hash" class="form-control"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="ladderMapImage"> Upload Image </label>
                                    <input type="file" name="mapImage" id="ladderMapImage" />
                                </div>

                                <button type="submit" class="btn btn-primary">Update</button>
                            </form>
                            <div class="col-md-6">
                                <div class="player-card" id="thumbnailContainer">
                                    <img class="img-fluid img-thumbnail" id="ladderMapThumbnail" src="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    <script type="text/javascript">

     let maps = {
         "new": { "map_id": "new" },
         @foreach($maps as $map)
         @if ($map->id != "new")
         "{{ $map->id }}": { "map_id": "{{$map->map_id}}" },
         @endif
         @endforeach
     };
     let ladderMaps = {
         @foreach($ladderMaps as $mph)
         "{{$mph->id}}": { "ladder_id": "{{$mph->ladder_id}}", "name": {!!json_encode($mph->name)!!}, "hash": {!! json_encode($mph->hash)!!} },
         @endforeach
         "new": { "ladder_id": "{{$rule->ladder_id}}", "name": "new map", "hash": "" },
     };

     (function () {
         let mapSels = document.querySelectorAll(".map-selector")
         for (let i = 0; i < mapSels.length; i++)
             {
                 mapSels[i].onchange = function ()
                 {
                     document.getElementById("mapThumbnail").src = "/images/maps/{{$ladderAbbrev}}/" + ladderMaps[this.value].hash + ".png";
                 }
             }
     })();

     (function () {
         document.getElementById("ladderMapSelector").onchange = function () {
             let ladderMap = ladderMaps[this.value];
             document.getElementById("ladderMapId").value = this.value;
             document.getElementById("ladderMapName").value = ladderMap.name;
             document.getElementById("ladderMapHash").value = ladderMap.hash;
             document.getElementById("ladderMapThumbnail").src = "/images/maps/{{$ladderAbbrev}}/" + ladderMap.hash +".png"
         };
     })();

     (function () {
         let mps = document.getElementById("mapPoolSelector");
         mps.onchange = function ()
         {
             document.getElementById("mapThumbnail").src = "/images/maps/{{$ladderAbbrev}}/" + ladderMaps[maps[this.value].map_id].hash + ".png";
             let hideList = document.querySelectorAll("div.map");
             for (let i = 0; i < hideList.length; i++)
             {
                 if (!hideList[i].classList.contains("hidden"))
                     hideList[i].classList.add("hidden");
             }
             document.getElementById("map_" + this.value).classList.remove("hidden");

             let mapSel = document.getElementById(this.value + "_map");

             if (mapSel.length == 0)
             {
                 for (map_id in ladderMaps)
                 {
                     let option = document.createElement("option");
                     option.value = map_id;
                     option.text = ladderMaps[map_id].name;
                     if (map_id == maps[this.value].map_id)
                         option.selected = true;
                     mapSel.add(option);
                 }
             }
             document.getElementById("ladderMapSelector").selectedIndex = document.getElementById(this.value + "_map").selectedIndex;
             document.getElementById("ladderMapSelector").onchange();

             mapSel.value = maps[this.value].map_id;
             document.getElementById("mapSelected").value = this.value;
         };
         if (mps.selectedIndex < 0)
             mps.selectedIndex = 0;
         mps.onchange();
     })();

    </script>
@endsection
