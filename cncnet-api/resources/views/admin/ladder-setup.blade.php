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
<section class="light-texture game-detail supported-games">
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <h2>Match Ladder Rules</h2>
                    <p class="lead">Manage the configurables for Quick Match</p>

                    @include("components.form-messages")
                    
                    <div class="row">
                        @foreach($rules as $rule)
                        <div class="col-md-4" style="margin-right: 25px;">
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

                                <?php $sides = \App\Side::where("ladder_id", "=", $rule->ladder_id)->get(); ?>
                                
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

                                <button type="submit" class="btn btn-secondary btn-lg">Save</button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection




