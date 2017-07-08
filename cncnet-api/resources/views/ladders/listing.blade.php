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
{{ $players }}
<section class="cncnet-features general-texture game-detail">
    <div class="container">

        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center" style="padding-bottom: 40px;">
                        <h1>{{ $ladder->name }}</h1>
                        <p class="lead">Find the latest competitive games</p>
                    </div>
                    <style>
                    .profile-listing 
                    {
                        background: black;
                        padding: 30px 50px;
                        border-radius: 5px;
                        margin: 15px 0;
                    }
                    .profile-link
                    {
                        display: block; 
                    }
                     .profile-link:hover
                    {
                        text-decoration: none;
                    }
                    </style>
                    <div class="row">
                        @foreach($players as $k => $player)
                        <div class="col-md-4">
                            <a href="/ladder/{{ $ladder->abbreviation }}/player/{{ $player->username }}" class="profile-link">
                            <div class="profile-listing">
                                <h3>Rank: <i class="fa fa-trophy fa-fw"></i> #{{ $k + 1 }}</h3>
                                <p>Player: <i class="fa fa-user fa-fw"></i> {{ $player->username }}</p>
                                <p>Points: <i class="fa fa-bolt fa-fw"></i> {{ $player->points }}</p>
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

