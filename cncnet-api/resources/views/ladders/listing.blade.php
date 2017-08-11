@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-{{ $history->ladder->abbreviation }}.jpg
@endsection

@if($history->ladder->abbreviation == "ra")
    <?php $dir = "red-alert"; ?>
@elseif($history->ladder->abbreviation == "ts")
    <?php $dir = "tiberian-sun"; ?>
@elseif($history->ladder->abbreviation == "yr")
    <?php $dir = "yuris-revenge"; ?>
@endif

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    <img src="/images/games/{{ $dir or "" }}/logo.png" class="logo" />
                </h1>
                <p class="text-uppercase">
                   Player Ladder <strong>{{ Carbon\Carbon::parse($history->starts)->format('m-Y') }}</strong>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="cncnet-features general-texture game-detail">
    <div class="container">

        <div class="row">
            <div class="col-md-12">
                @include('ladders._recent-games')
            </div>
        </div>

        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-left">
                        <h3>1vs1 Rankings</h3>
                    </div>

                    <div class="row">
                        @foreach($players as $k => $player)
                        <div class="col-md-4">
                            <a href="/ladder/{{ $history->short . "/" . $history->ladder->abbreviation }}/player/{{ $player->username }}" class="profile-link">
                                <div class="profile-listing">
                                    <div class="rank">
                                        <i class="rank {{ $player->badge($player->points)}}"></i> 
                                    </div>
                                    <h3>
                                        Rank #{{ $k + 1 }} <br>
                                        <small>Rating <strong>#{{ $player->rating()->first()->rating }}</strong></small>
                                    </h3> 
                                    <p class="username"><i class="fa fa-user fa-fw"></i> {{ $player->username }}</p>
                                    <p class="points"><i class="fa fa-bolt fa-fw"></i> {{ $player->points }}</p>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection

