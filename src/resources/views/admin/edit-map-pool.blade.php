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
                        @include('components.form-messages')
                    </div>
                    <div class="col-md-12">
                        <h2>{{ $mapPool->name }}</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editMapPool"> Edit
                        </button>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="rempoolmap">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                            <select id="mapPoolSelector" name="map_id" size="18" class="form-control">
                                <?php $lastmap = null; ?>
                                @foreach ($maps as $map)
                                    <option value="{{ $map->id }}"
                                        {{ old('map_selected') == $map->id ? 'selected' : '' }}>
                                        {{ $map->admin_description }}
                                    </option>
                                    <?php $lastmap = $map; ?>
                                @endforeach
                                @if ($lastmap !== null)
                                    <?php $new_map = $lastmap->replicate();
                                    $new_map->bit_idx++;
                                    $new_map->id = 'new';
                                    $new_map->description = 'Copy of ' . $lastmap->description;
                                    $new_map->admin_description = 'Copy of ' . $lastmap->admin_description;
                                    ?>
                                @else
                                    <?php $new_map = new App\Models\QmMap();
                                    $new_map->id = 'new';
                                    $new_map->map_pool_id = $mapPool->id;
                                    $new_map->valid = 1;
                                    ?>
                                @endif
                                <?php $maps->push($new_map); ?>
                                <option value="new">&lt;New Map></option>
                            </select>
                            <button type="submit" class="btn btn-danger">Remove Map</button>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#reorderMapPool">
                                Reorder </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="player-card" id="thumbnailContainer">
                            <img class="img-fluid img-thumbnail" id="mapThumbnail" src="">
                        </div>
                    </div>
                </div>
                @foreach ($maps as $map)
                    <div class="row map hidden" id="map_{{ $map->id }}">
                        <form method="POST" action="edit" class="map"
                            id="qmap_{{ $map->map_pool_id }}_{{ $map->id }}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                            <input type="hidden" name="id" value="{{ $map->id }}" />
                            <input type="hidden" name="map_pool_id" value="{{ $map->map_pool_id }}" />
                            <input type="hidden" name="bit_idx" value="{{ $map->bit_idx }}" />
                            <input type="hidden" name="valid" value="{{ $map->valid }}" />

                            <div class="form-group col-md-4">
                                <div class="form-group col-md-12">
                                    <label for="{{ $map->id }}_description"> Description </label>
                                    <input type="text" id="{{ $map->id }}_description" name="description"
                                        value="{{ $map->description }}" class="form-control" />
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="{{ $map->id }}_admin_description"> Admin Description </label>
                                    <input type="text" id="{{ $map->id }}_admin_description" name="admin_description"
                                        value="{{ $map->admin_description }}" class="form-control" />
                                </div>

                                <div class="form-group col-md-4">
                                    <label for="{{ $map->id }}_spawn_order">spawn_order</label>
                                    <input type="text" id="{{ $map->id }}_spawn_order" name="spawn_order"
                                        value="{{ $map->spawn_order }}" class="form-control" />
                                </div>

                                <div class="form-group col-md-4">
                                    <label for="{{ $map->id }}_team1_spawn_order">team1</label>
                                    <input type="text" id="{{ $map->id }}_team1_spawn_order" name="team1_spawn_order"
                                        value="{{ $map->team1_spawn_order }} " class="form-control" />
                                </div>

                                <div class="form-group col-md-4">
                                    <label for="{{ $map->id }}_team2_spawn_order">team2</label>
                                    <input type="text" id="{{ $map->id }}_team2_spawn_order" name="team2_spawn_order"
                                        value="{{ $map->team2_spawn_order }}" class="form-control" />
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="{{ $map->id }}_map"> map </label>
                                    <select id="{{ $map->id }}_map" name="map_id"
                                        class="form-control map-selector"></select>
                                    <button type="button" class="btn btn-primary btn-md" id="editMaps" data-toggle="modal"
                                        data-target="#editLadderMap"> Edit/New </button>
                                    @if ($ladderMaps->count() == 0)
                                        <button type="button" class="btn btn-primary" data-toggle="modal"
                                            data-target="#cloneLadderMaps"> Clone </button>
                                    @endif

                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="col-md-12">
                                    <?php $sideIdsAllowed = array_map('intval', explode(',', $map->allowed_sides)); ?>
                                    <label>Allowed Sides</label>
                                    <div class="overflow-auto" style="height: 250px; overflow: auto; background: black;">
                                        @foreach ($sides as $side)
                                            <div>
                                                <input id="side_{{ $side->id }}" type="checkbox" name="allowed_sides[]"
                                                    value="{{ $side->local_id }}"
                                                    @if (in_array($side->local_id, $sideIdsAllowed)) checked @endif />
                                                <label for="side_{{ $side->id }}">{{ $side->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <input id="{{ $map->id }}_rejectable" type="checkbox" name="rejectable"
                                        @if ($map->rejectable) checked @endif />
                                    <label for="{{ $map->id }}_rejectable"> Rejectable</label>
                                </div>

                                <div class="col-md-6">
                                    <input id="default_reject_{{ $map->id }}" type="checkbox" name="default_reject"
                                        @if ($map->default_reject) checked @endif />
                                    <label for="default_reject_{{ $map->id }}"> Rejected By Default</label>
                                </div>

                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary btn-lg">Save</button>
                                </div>
                            </div>
                        </form>

                        <div class="form-group col-md-4">
                            <label for="optionList_{{ $map->id }}"> Extra Options </label>
                            <select id="optionList_{{ $map->id }}" size="12" class="form-control optionList"
                                style="margin-bottom: 8px">
                                @if ($map->id != 'new')
                                    @foreach ($map->spawnOptionValues as $sov)
                                        <option value="{{ $sov->id }}">{{ $sov->spawnOption->name->string }} :
                                            {{ $sov->value->string }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <button type="button" class="btn btn-primary btn-md" data-toggle="modal"
                                data-target="#editOptions_{{ $map->id }}"> Edit </button>
                            <button type="button" class="btn btn-primary btn-md" data-toggle="modal"
                                data-target="#newOptions_{{ $map->id }}"> New </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="modal fade" id="editMapPool" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Edit Map Pool</h3>
                </div>
                <div class="modal-body clearfix">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                                <form method="POST" action="remove"
                                    onsubmit="return confirm('This action will delete the whole map pool and all maps permanently.');">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <button type="submit" class="btn btn-danger btn-md">Delete</button>
                                </form>
                                <form method="POST" action="rename">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <div class="form-group">
                                        <label for="map_pool_name"> Pool Name </label>
                                        <input type="text" id="map_pool_name" name="name" size="100"
                                            value="{{ $mapPool->name }}" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-md">Rename</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editLadderMap" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Edit Map Hashes</h3>
                </div>
                <div class="modal-body clearfix">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                                <form method="POST" action="remmap">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}" />
                                    <p style="color: #fff">Map Hashes</p>

                                    <select id="ladderMapSelector" name="map_id" size="6" class="form-control map_pool">
                                        @foreach ($ladderMaps as $map)
                                            <option value="{{ $map->id }}"> {{ $map->name }} </option>
                                        @endforeach
                                        <option value="new">&lt;new></option>
                                    </select>
                                </form>
                                <form method="POST" action="../../editmap" class="map" id="ladderMapEdit"
                                    enctype="multipart/form-data">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}" />
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
    @foreach ($maps as $map)
        <div class="modal fade" id="newOptions_{{ $map->id }}" tabIndex="-1" role="dialog">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <h3 class="modal-title">Edit Map Option</h3>
                    </div>
                    <div class="modal-body clearfix">
                        <div class="container-fluid">
                            <?php $new_sov = new \App\Models\SpawnOptionValue();
                            $new_sov->id = 'new';
                            $new_sov->value = ''; ?>
                            @include('admin.spawn-option', [
                                'sov' => $new_sov,
                                'ladderId' => '',
                                'qmMapId' => $map->id,
                                'spawnOptions' => $spawnOptions,
                                'button' => 'Add',
                                'hidden' => false,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="editOptions_{{ $map->id }}" tabIndex="-1" role="dialog">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <h3 class="modal-title">Edit Map Option</h3>
                    </div>
                    <div class="modal-body clearfix">
                        <div class="container-fluid">
                            <?php $new_sov = new \App\Models\SpawnOptionValue();
                            $new_sov->id = 'new';
                            $new_sov->value = ''; ?>
                            @include('admin.spawn-option', [
                                'sov' => $new_sov,
                                'ladderId' => '',
                                'qmMapId' => $map->id,
                                'spawnOptions' => $spawnOptions,
                                'button' => 'Add',
                                'hidden' => true,
                            ])
                            @foreach ($map->spawnOptionValues as $sov)
                                @include('admin.spawn-option', [
                                    'sov' => $sov,
                                    'ladderId' => '',
                                    'qmMapId' => $map->id,
                                    'spawnOptions' => $spawnOptions,
                                    'button' => 'Update',
                                    'hidden' => true,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="cloneLadderMaps" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Seed the Map Pool</h3>
                </div>
                <div class="modal-body clearfix">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                                <form method="POST" action="cloneladdermaps">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}">
                                    <div class="form-group">
                                        <label for="cloneSelector">Clone From</label>
                                        <select name="clone_ladder_id" id="cloneSelector" class="form-control">
                                            @foreach ($allLadders as $ladder)
                                                <option value="{{ $ladder->id }}">{{ $ladder->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" value="clone" name="submit"
                                        class="btn btn-lg btn-primary">Clone</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="reorderMapPool" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Reorder the Map Pool</h3>
                </div>
                <div class="modal-body clearfix">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                                <form method="POST" action="reorder">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" id="mapPoolId" name="id" value="{{ $mapPool->id }}" />
                                    <ul id="mapList"
                                        style="overflow: auto;max-height: calc(100vh - 300px); list-style: none; margin: 0; padding: 0;">
                                        @foreach ($maps as $map)
                                            @if ($map->id != 'new')
                                                <li class="map-in-list" value="{{ $map->bit_idx }}">
                                                    <input type="radio" id="rinput_idx_{{ $map->bit_idx }}"
                                                        name="maphandle" value="{{ $map->id }},{{ $map->bit_idx }}"
                                                        class="maphandle"></radio>
                                                    <label id="linput_idx_{{ $map->bit_idx }}"
                                                        for="rinput_idx_{{ $map->bit_idx }}"
                                                        style="margin-bottom: 0;">{{ $map->admin_description }}</label>
                                                    <input type="hidden" id="input_idx_{{ $map->bit_idx }}"
                                                        name="bit_idx_{{ $map->bit_idx }}"
                                                        value="{{ $map->id }}" />
                                                </li>
                                            @endif
                                        @endforeach
                            </div>
                            <div style="margin-top: 8px;">
                                <a href="#reorderMapPool" class="move-up btn btn-primary"><span
                                        class="fa fa-arrow-up"></span></a>
                                <a href="#reorderMapPool" class="move-down btn btn-primary"><span
                                        class="fa fa-arrow-down"></span></a>
                                <a href="#reorderMapPool" class="alphabetical-sort btn btn-danger"><span>A-Z</span></a>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
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
    <script type="text/javascript">
        let maps = {
            "new": {
                "map_id": "new"
            },
            @foreach ($maps as $map)
                @if ($map->id != 'new')
                    "{{ $map->id }}": {
                        "map_id": "{{ $map->map_id }}"
                    },
                @endif
            @endforeach
        };
        let ladderMaps = {
            @foreach ($ladderMaps as $mph)
                "{{ $mph->id }}": {
                    "ladder_id": "{{ $mph->ladder_id }}",
                    "name": {!! json_encode($mph->name) !!},
                    "hash": {!! json_encode($mph->hash) !!}
                },
            @endforeach
            "new": {
                "ladder_id": "{{ $ladder->id }}",
                "name": "new map",
                "hash": ""
            },
        };

        (function() {
            let mapSels = document.querySelectorAll(".map-selector")
            for (let i = 0; i < mapSels.length; i++) {
                mapSels[i].onchange = function() {
                    document.getElementById("mapThumbnail").src = "/images/maps/{{ $ladderAbbrev }}/" +
                        ladderMaps[
                            this.value].hash + ".png";
                }
            }
        })();

        (function() {
            document.getElementById("ladderMapSelector").onchange = function() {
                let ladderMap = ladderMaps[this.value];
                document.getElementById("ladderMapId").value = this.value;
                document.getElementById("ladderMapName").value = ladderMap.name;
                document.getElementById("ladderMapHash").value = ladderMap.hash;
                document.getElementById("ladderMapThumbnail").src = "/images/maps/{{ $ladderAbbrev }}/" +
                    ladderMap
                    .hash + ".png"
            };
        })();

        (function() {
            let mps = document.getElementById("mapPoolSelector");
            mps.onchange = function() {
                document.getElementById("mapThumbnail").src = "/images/maps/{{ $ladderAbbrev }}/" + ladderMaps[
                    maps[this.value].map_id].hash + ".png";
                let hideList = document.querySelectorAll("div.map");
                for (let i = 0; i < hideList.length; i++) {
                    if (!hideList[i].classList.contains("hidden"))
                        hideList[i].classList.add("hidden");
                }
                document.getElementById("map_" + this.value).classList.remove("hidden");

                let mapSel = document.getElementById(this.value + "_map");

                if (mapSel.length == 0) {
                    for (map_id in ladderMaps) {
                        let option = document.createElement("option");
                        option.value = map_id;
                        option.text = ladderMaps[map_id].name;
                        if (map_id == maps[this.value].map_id)
                            option.selected = true;
                        mapSel.add(option);
                    }
                }
                document.getElementById("ladderMapSelector").selectedIndex = document.getElementById(this.value +
                    "_map").selectedIndex;
                document.getElementById("ladderMapSelector").onchange();

                mapSel.value = maps[this.value].map_id;
                document.getElementById("mapSelected").value = this.value;
            };
            if (mps.selectedIndex < 0)
                mps.selectedIndex = 0;
            mps.onchange();
        })();

        (function() {
            let opts = document.querySelectorAll(".optionList");
            for (let x = 0; x < opts.length; x++) {
                opts[x].onchange = function() {
                    let optList = document.querySelectorAll(".option");
                    for (let i = 0; i < optList.length; i++) {
                        if (!optList[i].classList.contains('hidden') && !optList[i].classList.contains('new'))
                            optList[i].classList.add("hidden");
                    }
                    document.getElementById("option_" + this.value).classList.remove("hidden");
                };
            }
        })();

        (function() {
            let ups = document.querySelectorAll(".move-up");
            for (let i = 0; i < ups.length; i++) {
                ups[i].addEventListener('click', function() {

                    let ele = document.getElementsByName('maphandle');

                    for (i = 0; i < ele.length; i++) {
                        if (ele[i].checked) {
                            let parts = ele[i].value.split(",");
                            let prev_bit = parseInt(parts[1]) - 1;
                            if (prev_bit < 0)
                                break;

                            let hid = document.getElementById("input_idx_" + parts[1]);
                            let lab = document.getElementById("linput_idx_" + parts[1]);
                            let rad = document.getElementById("rinput_idx_" + parts[1]);

                            let prev_hid = document.getElementById("input_idx_" + prev_bit);
                            let prev_lab = document.getElementById("linput_idx_" + prev_bit);
                            let prev_rad = document.getElementById("rinput_idx_" + prev_bit);

                            if (prev_hid !== null) {
                                let desc = lab.innerHTML;
                                let hval = hid.value;
                                let rval = rad.value;

                                lab.innerHTML = prev_lab.innerHTML;
                                prev_lab.innerHTML = desc;

                                hid.value = prev_hid.value;
                                prev_hid.value = hval;

                                rad.value = hid.value + "," + parts[1];
                                prev_rad.value = prev_hid.value + "," + prev_bit;

                                ele[i - 1].checked = true;
                                break;
                            }
                        }
                    }
                });
            }

            let downs = document.querySelectorAll(".move-down");
            for (i = 0; i < downs.length; i++) {
                downs[i].addEventListener('click', function() {

                    let ele = document.getElementsByName('maphandle');

                    for (i = 0; i < ele.length; i++) {
                        if (ele[i].checked) {
                            let parts = ele[i].value.split(",");
                            let next_bit = parseInt(parts[1]) + 1;

                            let hid = document.getElementById("input_idx_" + parts[1]);
                            let lab = document.getElementById("linput_idx_" + parts[1]);
                            let rad = document.getElementById("rinput_idx_" + parts[1]);
                            let next_hid = document.getElementById("input_idx_" + next_bit);
                            let next_lab = document.getElementById("linput_idx_" + next_bit);
                            let next_rad = document.getElementById("rinput_idx_" + next_bit);

                            if (next_hid !== null) {
                                let desc = lab.innerHTML;
                                let hval = hid.value;
                                let rval = rad.value;

                                lab.innerHTML = next_lab.innerHTML;
                                next_lab.innerHTML = desc;

                                hid.value = next_hid.value;
                                next_hid.value = hval;

                                rad.value = hid.value + "," + parts[1];
                                next_rad.value = next_hid.value + "," + next_bit;

                                ele[i + 1].checked = true;
                                break;
                            }
                        }
                    }
                });
            }

            let alphabeticalSorts = document.querySelectorAll(".alphabetical-sort");
            for (i = 0; i < alphabeticalSorts.length; i++) {

                alphabeticalSorts[i].addEventListener('click', function() {

                    var mapList = document.getElementById("mapList");

                    var i = 0;
                    Array.from(mapList.getElementsByTagName("li"))
                        .sort((a, b) => a.textContent.localeCompare(b.textContent))
                        .forEach(x => {

                            let rinput = x.getElementsByTagName("input")[0];
                            let parts = rinput.value.split(",");
                            rinput.value = parts[i] + "," + i;

                            let hinput = x.getElementsByTagName("input")[1];
                            hinput.value = parts[0];
                            hinput.id = "input_idx_" + i;
                            hinput.name = "bit_idx_" + i;

                            x.value = i;
                            mapList.appendChild(x);
                            i++;
                        });
                });
            }
        })();
    </script>
@endsection
