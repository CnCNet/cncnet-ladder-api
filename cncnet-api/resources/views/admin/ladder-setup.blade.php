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
<script>
   function showMapEdit(e, id, c) {
       document.querySelectorAll('form.qmMap').forEach(function(element) { element.style.display = 'none' });
       document.querySelectorAll('select.qmPool').forEach(function(element) { if (element.selectedIndex >= 0) element.options[element.selectedIndex].selected = false });
       if (e !== null && id !== '')
       {
           e.selected = true;
           document.getElementById(id).style.display = 'block';
       }
   }
</script>

<section class="general-texture">
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <h2>Match Ladder Rules</h2>
                    <p class="lead">Manage the configurables for Quick Match</p>

                    @include("components.form-messages")

                    <div class="row">
                        @foreach($rules as $rule)
                        <div class="col-md-4">
                            <div class="player-box player-card">
                            <p style="color: #fff">{{ \App\Ladder::find($rule->ladder_id)->name }}</p>

                            <form method="POST" action="/admin/setup/rules">
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
                                    <label for="{{ $rule->ladder_id }}_point_difference">Max Point Difference</label>
                                    <input id="{{ $rule->ladder_id }}_point_difference" type="number" name="max_difference" class="form-control" value="{{ $rule->max_difference }}" />
                                </div>

                                <?php $sides = \App\Side::where("ladder_id", "=", $rule->ladder_id)->orderby('local_id', 'ASC')->get(); ?>

                                <div class="form-group">
                                    <label for="{{ $rule->ladder_id }}_sides">All Sides</label>

                                    <?php $sideIds = explode(',', $rule->all_sides); ?>
                                    <select id="{{ $rule->ladder_id }}_sides" name="all_sides[]" class="form-control" multiple>
                                        @foreach($sides as $side)
                                        <option value="{{ $side->local_id }}" @if(in_array($side->local_id, $sideIds)) selected @endif > {{ $side->name }} </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="{{ $rule->ladder_id }}_allowed_sides">Allowed Sides</label>

                                    <?php $sideIdsAllowed = explode(',', $rule->allowed_sides); ?>
                                    <select id="{{ $rule->ladder_id }}_allowed_sides" name="allowed_sides[]" class="form-control" multiple>
                                        @foreach($sides as $side)
                                        <option value="{{ $side->local_id }}" @if(in_array($side->local_id, $sideIdsAllowed)) selected @endif > {{ $side->name }} </option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg">Save</button>
                            </form>

                            <div class="form-group">
                              <form method="POST" action="/admin/setup/remqmap">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input type="hidden" name="ladder_id" value="{{ $rule->ladder_id }}" />

                                <label for="{{ $rule->ladder_id }}_ladder_id">QuickMatch Map Pool</label>
                                <select id ="{{ $rule->ladder_id }}_ladder_id" name="map_id" size="6" class="form-control qmPool">
                                    <?php $last_idx  = -1;
                                          $new_map = new \App\QmMap();
                                          $new_map->bit_idx = -1;
                                          $new_map->ladder_id = $rule->ladder_id;
                                          $new_map->valid = true;
                                    ?>
                                    @foreach($qmMaps as $qmMap)
                                        @if($qmMap->ladder_id == $rule->ladder_id && $qmMap->valid)
                                            <option value="{{ $qmMap->id }}" onclick="showMapEdit(this, 'map_{{ $qmMap->ladder_id }}_{{ $qmMap->id }}')"> {{ $qmMap->bit_idx }} : {{ $qmMap->description }} </option>
                                            <?php $new_map = $qmMap; ?>
                                        @endif
                                    @endforeach

                                    @if($new_map->bit_idx < 31)
                                        <?php $new_map = $new_map->replicate();
                                              $new_map->bit_idx++;
                                              $new_map->id = 'new';
                                              $new_map->description = "Copy of " . $new_map->description;
                                              $qmMaps->push($new_map);
                                        ?>
                                        <option value="{{ $new_map->bit_idx }}" onclick="showMapEdit(this,'map_{{ $new_map->ladder_id }}_new')"> {{ $new_map->bit_idx }} : &lt;new> </option>
                                    @endif
                                </select>
                                <button type="submit" class="btn btn-danger btn-lg">Remove Map</button>
                              </form>
                            </div>

                             @foreach($qmMaps as $qmMap)
                                 @if($qmMap->ladder_id == $rule->ladder_id && $qmMap->valid)
                                 <form method="POST" action="/admin/setup/qmmap" class="qmMap" id="map_{{ $qmMap->ladder_id }}_{{ $qmMap->id }}">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                        <input type="hidden" name="id" value="{{ $qmMap->id }}" />
                                        <input type="hidden" name="ladder_id" value="{{ $rule->ladder_id }}" />
                                        <input type="hidden" name="bit_idx" value="{{ $qmMap->bit_idx }}" />
                                        <input type="hidden" name="valid" value="{{ $qmMap->valid }}" />

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_description"> Description </label>
                                            <input type="text" id="{{ $qmMap->id }}_description" name="description" value="{{ $qmMap->description }}" class="form-control" />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_spawn_order">spawn_order</label>
                                            <input type="text" id="{{ $qmMap->id }}_spawn_order" name="spawn_order" value="{{ $qmMap->spawn_order }}" class="form-control" />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_team1_spawn_order">team1_spawn_order</label>
                                            <input type="text" id="{{ $qmMap->id }}_team1_spawn_order" name="team1_spawn_order" value="{{ $qmMap->team1_spawn_order }} "class="form-control"  />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_team2_spawn_order">team2_spawn_order</label>
                                            <input type="text" id="{{ $qmMap->id }}_team2_spawn_order" name="team2_spawn_order" value="{{ $qmMap->team2_spawn_order }}" class="form-control"  />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_game_mode">game_mode</label>
                                            <input type="text" id="{{ $qmMap->id }}_game_mode" name="game_mode" value="{{ $qmMap->game_mode }}" class="form-control"  />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_ai_player_count"> ai_player_count </label>
                                            <input id="{{ $qmMap->id }}_ai_player_count" name="ai_player_count" type="number" value="{{ $qmMap->ai_player_count }}" class="form-control"  />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_map"> map </label>
                                            <select id="{{ $qmMap->id }}_map" name="map_id" class="form-control" >
                                            @foreach($maps as $map)
                                                <option value="{{ $map->id }}" @if($qmMap->map_id == $map->id) selected @endif > {{ $map->name }} </option>
                                            @endforeach
                                            </select>
                                         </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_speed"> speed </label>
                                            <select id="{{ $qmMap->id }}_speed" name="speed" class="form-control" >
                                            @foreach(range(0,6) as $speed)
                                                <option value="{{ $speed }}" @if($qmMap->speed == $speed) selected @endif > {{ $speed }} </option>
                                            @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_credits"> credits </label>
                                            <input id="{{ $qmMap->id }}_credits" name="credits" type="number" value="{{ $qmMap->credits }}" class="form-control" />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_units"> units </label>
                                            <input id="{{ $qmMap->id }}_units" name="units" type="number" value="{{ $qmMap->units }}" class="form-control" />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_tech"> tech </label>
                                            <input id="{{ $qmMap->id }}_tech" name="tech" type="number" value="{{ $qmMap->tech }}" class="form-control" />
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_firestorm"> firestorm </label>
                                            <select id="{{ $qmMap->id }}_firestorm" name="firestorm" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->firestorm)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->firestorm === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->firestorm === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_ra2_mode"> ra2_mode </label>
                                            <select id="{{ $qmMap->id }}_ra2_mode" name="ra2_mode" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->ra2_mode)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->ra2_mode === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->ra2_mode === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_short_game"> short_game </label>
                                            <select id="{{ $qmMap->id }}_short_game" name="short_game" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->short_game)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->short_game === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->short_game === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_multi_eng"> multi_eng </label>
                                            <select id="{{ $qmMap->id }}_multi_eng" name="multi_eng" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->multi_eng)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->multi_eng === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->multi_eng === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_dog_kill"> No Dog Eat Engineer </label>
                                            <select id="{{ $qmMap->id }}_dog_kill" name="dog_kill" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->dog_kill)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->dog_kill === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->dog_kill === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_redeploy"> redeploy </label>
                                            <select id="{{ $qmMap->id }}_redeploy" name="redeploy" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->redeploy)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->redeploy === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->redeploy === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_crates"> crates </label>
                                            <select id="{{ $qmMap->id }}_crates" name="crates" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->crates)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->crates === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->crates === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_bases"> bases </label>
                                            <select id="{{ $qmMap->id }}_bases" name="bases" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->bases)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->bases === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->bases === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_harv_truce"> harvester_truce </label>
                                            <select id="{{ $qmMap->id }}_harv_truce" name="harv_truce" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->harv_truce)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->harv_truce === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->harv_truce === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_allies"> allies </label>
                                            <select id="{{ $qmMap->id }}_allies" name="allies" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->allies)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->allies === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->allies === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_bridges"> bridge_destroy </label>
                                            <select id="{{ $qmMap->id }}_bridges" name="bridges" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->bridges)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->bridges === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->bridges === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_fog"> fog </label>
                                            <select id="{{ $qmMap->id }}_fog" name="fog" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->fog)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->fog === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->fog === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_build_ally"> build_ally </label>
                                            <select id="{{ $qmMap->id }}_build_ally" name="build_ally" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->build_ally)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->build_ally === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->build_ally === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_multi_factory"> multi_factory </label>
                                            <select id="{{ $qmMap->id }}_multi_factory" name="multi_factory" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->multi_factory)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->multi_factory === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->multi_factory === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_aimable_sams"> aimable_sams </label>
                                            <select id="{{ $qmMap->id }}_aimable_sams" name="aimable_sams" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->aimable_sams)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->aimable_sams === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->aimable_sams === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_attack_neutral"> attack_neutral </label>
                                            <select id="{{ $qmMap->id }}_attack_neutral" name="attack_neutral" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->attack_neutral)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->attack_neutral === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->attack_neutral === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_supers"> supers </label>
                                            <select id="{{ $qmMap->id }}_supers" name="supers" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->supers)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->supers === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->supers === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_spawn_preview"> spawn_preview </label>
                                            <select id="{{ $qmMap->id }}_spawn_preview" name="spawn_preview" class="form-control" >
                                                <option value="Null" @if(is_null($qmMap->spawn_preview)) selected @endif>Null</option>
                                                <option value="No" @if($qmMap->spawn_preview === 0) selected @endif>No</option>
                                                <option value="Yes" @if($qmMap->spawn_preview === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>


                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_fix_ai_ally"> fix_ai_ally </label>
                                            <select id="{{ $qmMap->id }}_fix_ai_ally" name="fix_ai_ally" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->fix_ai_ally)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->fix_ai_ally === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->fix_ai_ally === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_ally_reveal"> ally_reveal </label>
                                            <select id="{{ $qmMap->id }}_ally_reveal" name="ally_reveal" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->ally_reveal)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->ally_reveal === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->ally_reveal === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_am_fast_build"> am_fast_build </label>
                                            <select id="{{ $qmMap->id }}_am_fast_build" name="am_fast_build" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->am_fast_build)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->am_fast_build === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->am_fast_build === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_parabombs"> parabombs </label>
                                            <select id="{{ $qmMap->id }}_parabombs" name="parabombs" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->parabombs)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->parabombs === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->parabombs === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_fix_formation_speed"> fix_formation_speed </label>
                                            <select id="{{ $qmMap->id }}_fix_formation_speed" name="fix_formation_speed" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->fix_formation_speed)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->fix_formation_speed === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->fix_formation_speed === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_fix_magic_build"> fix_magic_build </label>
                                            <select id="{{ $qmMap->id }}_fix_magic_build" name="fix_magic_build" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->fix_magic_build)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->fix_magic_build === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->fix_magic_build === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_fix_range_exploit"> fix_range_exploit </label>
                                            <select id="{{ $qmMap->id }}_fix_range_exploit" name="fix_range_exploit" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->fix_range_exploit)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->fix_range_exploit === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->fix_range_exploit === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_super_tesla_fix"> super_tesla_fix </label>
                                            <select id="{{ $qmMap->id }}_super_tesla_fix" name="super_tesla_fix" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->super_tesla_fix)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->super_tesla_fix === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->super_tesla_fix === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_forced_alliances"> forced_alliances </label>
                                            <select id="{{ $qmMap->id }}_forced_alliances" name="forced_alliances" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->forced_alliances)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->forced_alliances === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->forced_alliances === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_tech_center_fix"> tech_center_fix </label>
                                            <select id="{{ $qmMap->id }}_tech_center_fix" name="tech_center_fix" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->tech_center_fix)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->tech_center_fix === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->tech_center_fix === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_no_screen_shake"> no_screen_shake </label>
                                            <select id="{{ $qmMap->id }}_no_screen_shake" name="no_screen_shake" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->no_screen_shake)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->no_screen_shake === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->no_screen_shake === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_no_tesla_delay"> no_tesla_delay </label>
                                            <select id="{{ $qmMap->id }}_no_tesla_delay" name="no_tesla_delay" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->no_tesla_delay)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->no_tesla_delay === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->no_tesla_delay === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <Label for="{{ $qmMap->id }}_dead_player_radar"> dead_player_radar </label>
                                            <select id="{{ $qmMap->id }}_dead_player_radar" name="dead_player_radar" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->dead_player_radar)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->dead_player_radar === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->dead_player_radar === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_capture_flag"> capture_flag </label>
                                            <select id="{{ $qmMap->id }}_capture_flag" name="capture_flag" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->capture_flag)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->capture_flag === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->capture_flag === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_slow_unit_build"> slow_unit_build </label>
                                            <select id="{{ $qmMap->id }}_slow_unit_build" name="slow_unit_build" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->slow_unit_build)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->slow_unit_build === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->slow_unit_build === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="{{ $qmMap->id }}_shroud_regrows"> shroud_regrows </label>
                                            <select id="{{ $qmMap->id }}_shroud_regrows" name="shroud_regrows" class="form-control" >
                                               <option value="Null" @if(is_null($qmMap->shroud_regrows)) selected @endif>Null</option>
                                               <option value="No" @if($qmMap->shroud_regrows === 0) selected @endif>No</option>
                                               <option value="Yes" @if($qmMap->shroud_regrows === 1) selected @endif>Yes</option>
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-lg">Save</button>
                                    </form>
                                    @endif
                                @endforeach
                            </div>
                            <script> showMapEdit(null,'') </script>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
