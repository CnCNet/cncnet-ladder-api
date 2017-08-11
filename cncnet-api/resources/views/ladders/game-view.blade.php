@extends('layouts.app')
@section('title', 'Ladder')

@section('css')
<link rel="stylesheet" href="/css/dials.css" />
@endsection

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
                <a href="/ladder/{{ $history->short . "/" . $history->ladder->abbreviation }}/player/" class="btn btn-transparent btn-lg">
                    <i class="fa fa-chevron-left fa-lg fa-fw" aria-hidden="true"></i> Player Leaderboard
                </a>
                <br>
                <?php $raw = \App\GameRaw::where("game_id", "=", $game->id)->get(); ?>
                @foreach($raw as $r)
                Raw Stats: <a href="/api/v1/ladder/raw/{{ $r != null ? $r->id : ""}} " target="_blank">{{ $r != null ? $r->id : "" }}</a><br>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="game-detail">
    <div class="container">
        <div class="game-profile">

            @foreach($stats as $k => $stat)
                <?php $gameStats = \App\Stats::where("id", "=", $stat->stats_id)->first(); ?>
                <div class="hidden-xs faction faction-{{ $gameStats->faction($history->ladder->abbreviation, $gameStats->cty) }} @if($k == 0)faction-left @else faction-right @endif"></div>
            @endforeach

            <div class="row">
                <div class="col-md-10 col-md-offset-1 text-center">

                    <h3 class="game-intro"> 
                        @foreach($stats as $k => $stat)
                            <?php $gameStats = \App\Stats::where("id", "=", $stat->stats_id)->first(); ?>
                            <?php $player = \App\Player::where("id", "=", $stat->player_id)->first(); ?>
                            <?php $points = \App\PlayerPoint::where("game_id", "=", $game->id)
                                    ->where("player_id", "=", $player->id)
                                    ->first();
                            ?>
                            @if ($points != null)
                                <span class="player">
                                    {{ $player->username or "Unknown" }} <strong>+{{ $points->points_awarded or "" }}</strong>
                                    @if($points->game_won) 
                                        <i class="fa fa-trophy fa-fw" style="color: #E91E63;"></i> 
                                    @else 
                                        <i class="fa fa-sun-o fa-fw" style="color: #00BCD4;"></i> 
                                    @endif
                                </span>
                        
                                @if (count($stats) == 1)
                                    <?php $points = \App\PlayerPoint::where("game_id", "=", $game->id)
                                        ->where("player_id", "!=", $player->id)
                                        ->first();
                                    ?>
                                    @if ($points != null)
                                    <span class="player">
                                        {{ $points->player()->first()->username or "Unknown" }} <strong>+{{ $points->points_awarded or "" }}</strong>
                                        @if ($points->game_won) 
                                            <i class="fa fa-trophy fa-fw" style="color: #E91E63;"></i> 
                                        @else 
                                            <i class="fa fa-sun-o fa-fw" style="color: #00BCD4;"></i> 
                                        @endif
                                    </span>
                                    @endif
                                @endif
                            @endif
                        @endforeach
                    </h3>

                    <div class="feature-map">
                        <p class="points">{{ $game->scen }}</p>

                        <?php $map = \App\Map::where("hash", "=", $game->hash)->first(); ?>
                        @if ($map != null)
                        <div class="feature-map text-center">
                            <img src="/images/maps/{{ $history->ladder->abbreviation}}/{{ $map->hash . ".png" }}">
                        </div>
                        @endif
                    </div>

                    <ul class="list-inline game-details">
                        <li><strong>Short Game:</strong> {{ $game->shrt ? "On" : "Off" }}</li>
                        <li><strong>Superweapons:</strong> {{ $game->supr ? "On" : "Off" }}</li>
                        <li><strong>Crates:</strong> {{ $game->crat ? "On" : "Off" }}</li>
                        <li><strong>Credits:</strong> {{ $game->cred }}</li>
                        <li><strong>Duration:</strong> {{ gmdate("H:i:s", $game->dura) }}</li>
                        <li><strong>MCV Redeploy:</strong> {{ $game->bamr & 1 ? "On" : "Off" }}</li>                 
                        <li><strong>Build off Ally Conyard:</strong> {{ $game->bamr & 2 ? "On" : "Off" }}</li>
                        <li><strong>Average FPS:</strong> {{ $game->afps }}</li>
                        <li><strong>Reconnection Error:</strong> {{ $game->oosy ? "Yes" : "No" }}</li>
                        <li><strong>Unit Count Start:</strong> {{ $game->unit ? $game->unit : 0 }}</li>
                        <li><strong>Players in Game:</strong> {{ $game->plrs ? $game->plrs : 0 }}</li>
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
            <?php $rank = $player->rank($history, $player->username); ?>
            <?php $points = $player->playerPoints($history, $player->username); ?>

            <div class="col-md-6">
                <a href="/ladder/{{ $history->short . "/" . $history->ladder->abbreviation }}/player/{{ $player->username }}" class="profile-link">
                    <div class="profile-detail">
                        <div class="rank">
                            <i class="rank {{ $player->badge($points) }}"></i> 
                        </div>
                        <h3>Rank  #{{ $rank }}</h3> 
                        <p class="username"><i class="fa fa-user fa-fw"></i> {{ $player->username }}</p>
                        <p class="points"><i class="fa fa-bolt fa-fw"></i> {{ $points  }}</p>
                        <p class="points">
                            <?php $credits = json_decode($gameStats->crd); ?>
                            <strong>Funds Left: </strong> {{ $credits->value }} 
                        </p>
                        <p class="colour player-panel-{{ $gameStats->colour($gameStats->col) }}" style="width:25px;height:25px;"></p>
                        <div class="country">
                            <span class="flag-icon flag-icon-{{ $gameStats->country($gameStats->cty) }}"></span>
                        </div>
                    </div>
                </a>
                <div class="player-stats-panel">
                    <pre style="background: black; color: silver; border: none;">
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
                    </pre>
                </div>
            </div>
            @endforeach
        </div>    
    </div>
</section>

@endsection
