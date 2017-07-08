@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-yr.jpg
@endsection

@section('css')
<link rel="stylesheet" href="/css/dials.css" />
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    {{ $player->username }} <small>Battle Statistics</small> 
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
        <div class="profile">
            <div class="row">
                <div class="col-md-7 col-md-offset-1">
                    <h3 class="battle-percentage">
                        {{ $player->username }}
                    </h3>
                    <ul class="list-inline">
                        <li>
                            Points <strong>{{ $player->points }}</strong>
                            <i class="fa fa-bolt fa-fw"></i>
                        </li>
                        <li>
                            Won <strong>{{ $player->win_count }}</strong>
                            <i class="fa fa-level-up fa-fw"></i>
                        </li>
                        <li>
                            Disconnects <strong>0</strong>
                        </li>                
                        <li>
                            Reconnection Errors <strong>0</strong>
                        </li>
                    </ul>
                    
                    <ul class="list-inline">
                        <li>
                        @if ($player->win_count > 0)  
                        <?php $winPercent = number_format($player->win_count / ($player->win_count + $player->loss_count) * 100); ?>
                        <div class="c100 p{{ $winPercent }} center big green">
                            <p class="title">Winning</p>
                            <p class="value">{{ $winPercent }}%</p>
                            <div class="slice"><div class="bar"></div><div class="fill"></div></div>
                        </div>
                        @endif
                        </li>
                        <li>
                            <div class="c100 p77 center big purple">
                                <p class="title">Games</p>
                                <p class="value">{{ $player->games_count }}   <i class="fa fa-diamond fa-fw"></i></p>
                                <div class="slice"><div class="bar"></div><div class="fill"></div></div>
                            </div>
                        </li>
                        <li>
                            <div class="c100 p55 center big blue">
                                <p class="title">Average FPS</p>
                                <p class="value">55 <i class="fa fa-industry fa-fw"></i></p>
                                <div class="slice"><div class="bar"></div><div class="fill"></div></div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <div class="profile-rank text-right">
                    <ul class="list-unstyled">
                        <li class="rank">
                            <h1>Rank #{{ $rank or "Unranked" }}</h1>
                        </li>
                        @if($rank == 1)
                        <li class="rank-title gold">
                            General  <i class='fa fa-trophy fa-fw fa-2x'></i>
                        </li>
                        @elseif ($rank == 2)
                        <li class="rank-title silver">
                            Lieutenant General <i class='fa fa-trophy fa-fw fa-2x'></i>
                        </li>
                        @elseif ($rank == 3)
                        <li class="rank-title bronze">
                            Major General <i class='fa fa-trophy fa-fw fa-2x'></i>
                        </li>
                        @else
                        <li class="rank-title">
                            Lieutenant 
                        </li>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="cncnet-features dark-texture">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h3>Recent Games</h3>
                <div class="table-responsive">
                    <table class="table table-hover player-games">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>When <i class="fa fa-clock-o fa-fw"></i></th>
                            <th>Players in game <i class="fa fa-user fa-fw"></i></th>
                            <th>Game Details <i class="fa fa-level-down fa-fw"></i></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($player->games()->orderBy("id", "DESC")->get() as $game)
                        <?php $g = \App\Game::where("id", "=", $game->game_id)->first(); ?>

                        @if($g != null)
                        <?php $stats = \App\Game::find($game->game_id)->stats()->first(); ?>
                        <tr>
                            <td>
                                <ul class="list-unstyled">
                                    <li>Game Id: {{ $g->id }}</li>
                                    <?php $raw = \App\GameRaw::where("game_id", "=", $g->id)->get(); ?>
                                    @foreach($raw as $r)
                                    <li>Raw Stats: <a href="/api/v1/ladder/raw/{{ $r != null ? $r->id : ""}} " target="_blank">{{ $r != null ? $r->id : "" }}</a></li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                {{ $g->created_at->format('d/m/Y - H:i') }}
                            </td>
                            <td>
                                <ul class="list-inline">
                                <?php $playerGames = \App\PlayerGame::where("game_id", "=", $game->game_id)->get(); ?>
                                @foreach($playerGames as $pg)
                                <li>
                                    <?php 
                                        $player = $pg->player()->first();
                                        $points = \App\PlayerPoint::where("game_id", "=", $game->game_id)
                                        ->where("player_id", "=", $player->id)
                                        ->first();
                                    ?>
                                    @if(isset($points))      
                                    <a href="/ladder/{{ $ladder->abbreviation }}/player/{{$player->username}}">
                                        {{ $player->username }} 
                                            
                                        @if(isset($points))
                                        + {{ $points->points_awarded }}
                                        @endif

                                        @if($points->game_won) 
                                        <i class="fa fa-level-up fa-lg fa-fw" aria-hidden="true" style="color:green;"></i> 
                                        @else
                                        <i class="fa fa-level-down fa-lg fa-fw" aria-hidden="true" style="color:red"></i> 
                                        @endif
                                    </a>
                                    @endif
                                </li>
                                @endforeach
                                </ul>
                            </td>
                            <td>
                            <ul class="list-unstyled">
                                <li><i class="fa fa-map-marker fa-fw"></i> {{ $stats->scen or "Unknown" }}</li>
                            </ul>
                            </td>
                            <td>
                                <ul class="list-unstyled">
                                    <li>Starting Credits: {{ $g->cred }}</li>
                                    <li>Game Duration: {{ gmdate("H:i:s", $g->dura) }}</li>
                                    <li>Tournament: {{ $g->trny ? "Yes" : "No" }}</li>
                                    <li>MCV Redeploy: {{ $g->bamr == 1 || $g->bamr == 3 ? "On" : "Off" }}</li>                 
                                    <li>Build off Ally Conyard: {{ $g->bamr == 2 || $g->bamr == 3 ? "On" : "Off" }}</li>
                                    <li>Average FPS: {{ $g->afps }}</li>
                                    <li>Out of Sync: {{ $g->oosy ? "Yes" : "No" }}</li>
                                    <li>Crates: {{ $g->crat ? "On" : "Off" }}</li>
                                    <li>Superweapons: {{ $g->supr ? "On" : "Off" }}</li>
                                    <li>Unit Count Start: {{ $g->unit ? $g->unit : 0 }}</li>
                                    <li>Players in Game: {{ $g->plrs ? $g->plrs : 0 }}</li>
                                </ul>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection