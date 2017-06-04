@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-yr.jpg
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
                <div class="col-md-9">
                    <h3 class="battle-percentage">{{ $player->username }}
                         @if($player->win_count > 0)  
                        <span class="badge badge-winpercent text-uppercase">
                            Win Percentage - {{ $player->win_count / ($player->win_count + $player->loss_count) * 100 }}%
                        </span>
                        @endif
                    </h3>
                </div>
                <div class="col-md-3">
                    <div class="profile-rank text-right">
                    <ul class="list-unstyled">
                        <li class="rank">
                            <h2>Rank #{{ $rank or "Unranked" }}</h2>
                        </li>
                        <li class="rank-title gold">
                            <i class='fa fa-trophy fa-fw fa-2x'></i>
                            Colonel
                        </li>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
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
                            Total Games <strong>{{ $player->games_count }} </strong>
                            <i class="fa fa-diamond fa-fw"></i>
                        </li>                
                        <li>
                            FPS <strong></strong>
                            <i class="fa fa-industry fa-fw"></i>
                        </li>
                    </ul>
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
                                        ({{ $points->game_won ? "+" : "" }} {{ $points->points_awarded }})
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

