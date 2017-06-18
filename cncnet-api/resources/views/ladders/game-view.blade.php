@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-{{ $ladder->abbreviation }}.jpg
@endsection

@if($ladder->abbreviation == "ra")
    <?php $dir = "red-alert"; ?>
@elseif($ladder->abbreviation == "ts")
    <?php $dir = "tiberian-sun"; ?>
@elseif($ladder->abbreviation == "yr")
    <?php $dir = "yuris-revenge"; ?>
@endif

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    <img src="/images/games/{{ $dir }}/logo.png" class="logo" />
                </h1>
                <p class="text-uppercase">
                   Play. Compete. <strong>Conquer.</strong>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection


@section('content')
<section class="cncnet-features general-texture game-detail">
    <div class="container">

        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center" style="padding-bottom: 40px;">
                        <h1>{{ $ladder->name }}</h1>
                        <p class="lead">Find the latest competitive games</p>
                    </div>

                    <div class="table-responsive">
                        @foreach($gameStats as $gameStat)
                            <pre>
                                {{ $gameStat->game }}
                            </pre>
                            @if($gameStat->id == $playerStats->game_stats_id)
                            <pre>
                                <?php
                                    $pt = json_decode($playerStats->player_stats);
                                    print_r($pt);
                                ?>
                            </pre>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

