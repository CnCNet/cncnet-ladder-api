@extends('layouts.app')
@section('title', 'Ladder')

@section('css')
<link rel="stylesheet" href="/css/dials.css" />
@endsection

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
             
                <a href="/ladder/{{ $ladder->abbreviation }}/player/" class="btn btn-transparent btn-lg">
                    <i class="fa fa-chevron-left fa-lg fa-fw" aria-hidden="true"></i> Player Leaderboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="game-detail">
    <div class="container">
        <div class="game-profile">
            <div class="row">
                <div class="col-md-10 col-md-offset-1 text-center">

                    <h3 class="game-intro"> 
                        @foreach($stats as $k => $stat)
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

                    <div class="feature-map">
                        <p>{{ $game->scen }}</p>
                        <img src="https://grant.cnc-comm.com/tmp/d179f3b4590e7f014d0761d670c5374de9458ef5.png">
                    </div>

                    <ul class="list-inline game-details">
                        <li>Superweapons: {{ $game->supr ? "On" : "Off" }}</li>
                        <li>Crates: {{ $game->crat ? "On" : "Off" }}</li>
                        <li>Credits: {{ $game->cred }}</li>
                        <li>Duration: {{ gmdate("H:i:s", $game->dura) }}</li>
                        <li>MCV Redeploy: {{ $game->bamr }}</li>                 
                        <li>Build off Ally Conyard: {{ $game->bamr }}</li>
                        <li>Average FPS: {{ $game->afps }}</li>
                        <li>Reconnection Error: {{ $game->oosy ? "Yes" : "No" }}</li>
                        <li>Unit Count Start: {{ $game->unit ? $game->unit : 0 }}</li>
                        <li>Players in Game: {{ $game->plrs ? $game->plrs : 0 }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cncnet-features dark-texture">
    <div class="container">

        <div class="row">
            @foreach($stats as $k => $stat)
            <?php $gameStats = \App\Stats::where("id", "=", $stat->stats_id)->first(); ?>
            <?php $player = \App\Player::where("id", "=", $stat->player_id)->first(); ?>
            <?php $rank = $player->rank($ladder->abbreviation, $player->username); ?>

            <div class="col-md-6">
                <a href="/ladder/{{ $ladder->abbreviation }}/player/{{ $player->username }}" class="profile-link">
                    <div class="profile-listing">
                        <div class="rank">
                            </ul>
                            <i class="rank rank-01-e9-3"></i> 
                        </div>
                        <h3>Rank  #{{ $rank }} </h3> 
                        <p class="username"><i class="fa fa-user fa-fw"></i> {{ $player->username }}</p>
                        <p class="points"><i class="fa fa-bolt fa-fw"></i> {{ $player->points }}</p>
                    </div>
                </a>

                <div class="stats clearfix">
                <pre style="background: black; color: silver;">
                    Colour: {{ $gameStats->col }}             
                    Country: {{ $gameStats->cty }}           
                    Credits: {{ $gameStats->crd }}             
                    Infantry Left: {{ $gameStats->inl }}       
                    Planes Left: {{ $gameStats->pll }}        
                    Buildings Left: {{ $gameStats->bll }}      
                    Units Bought: {{ $gameStats->unb }}        
                    Infantry Bought: {{ $gameStats->inb }}     
                    Planes Bought: {{ $gameStats->plb }}       
                    Buildings Bought: {{ $gameStats->blb }}    
                    Units Bought: {{ $gameStats->unk }}        
                    Infantry Bought: {{ $gameStats->ink }}     
                    Planes Killed: {{ $gameStats->plk }}       
                    Buildings Destroyed: {{ $gameStats->blk }}  
                    Buildings Captured: {{ $gameStats->blc }}  
                    Crates Found: {{ $gameStats->cra }}        
                    Harvested: {{ $gameStats->hrv }}            
                </pre>
                </div>
            </div>
            @endforeach
        </div>    
    </div>
</section>

@endsection
