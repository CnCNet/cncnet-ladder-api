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

        <div class="row">
            <div class="col-md-12">
                <div class="text-left">
                    <h3>Recent Games</h3>
                    <div class="recent-games">
                        <div class="row">
                            @foreach($games as $game)
                            <div class="col-md-3">
                                <a href="/ladder/{{$ladder->abbreviation . "/games/" . $game->id }}" class="profile-link">
                                    <div class="profile-listing">
                                        <?php $map = \App\Map::where("hash", "=", $game->hash)->first(); ?>
                                        @if ($map != null)
                                        <div class="feature-map text-center">
                                            <img src="/images/maps/{{ $ladder->abbreviation}}/{{ $map->hash . ".png" }}">
                                        </div>
                                        @endif
                                        <p class="username text-center" style="margin-bottom:0">1vs1</p>
                                        <p class="points text-center">{{ $game->scen or "Unknown" }}</p>
                                        <h3 class="game-intro"> 
                                        @foreach($game->stats as $k => $stat)
                                            <?php $player = \App\Player::where("id", "=", $stat->player_id)->first(); ?>
                                            <?php $points = \App\PlayerPoint::where("game_id", "=", $game->id)
                                                    ->where("player_id", "=", $player->id)
                                                    ->first();
                                            ?>
                                          
                                            <span class="player">
                                                {{ $player->username }} +{{ $points->points_awarded }}
                                                @if($points->game_won) 
                                                    <i class="fa fa-trophy fa-fw" style="color: #E91E63;"></i> 
                                                @else 
                                                    <i class="fa fa-sun-o fa-fw" style="color: #00BCD4;"></i> 
                                                @endif
                                            </span>
                                        @endforeach
                                        </h3>
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
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
                            <a href="/ladder/{{ $ladder->abbreviation }}/player/{{ $player->username }}" class="profile-link">
                                <div class="profile-listing">
                                    <div class="rank">
                                        </ul>
                                        <i class="rank rank-01-e9-3"></i> 
                                    </div>
                                    <h3>Rank  #{{ $k + 1 }} </h3> 
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

