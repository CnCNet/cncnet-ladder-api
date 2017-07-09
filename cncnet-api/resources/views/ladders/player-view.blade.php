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
                    {{ $player->username or "" }} <small>Battle Statistics</small> 
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
                            Points <strong>{{ $player->points or "" }}</strong>
                            <i class="fa fa-bolt fa-fw"></i>
                        </li>
                        <li>
                            Won <strong>{{ $player->win_count or "" }}</strong>
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
                        @if ($player->games_won > 0)  
                        <?php $winPercent = number_format($player->games_won / ($player->games_won + $player->games_lost) * 100); ?>
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
                                <p class="value"> {{ $player->game_count }}   <i class="fa fa-diamond fa-fw"></i></p>
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
                            <h1>Rank #{{ $player->rank == -1 ? "Unranked" : $player->rank }}</h1>
                        </li>
                        <li class="rank-title">
                            Lieutenant 
                        </li>
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
         
                <ul>
                @foreach($games as $g)
                @if($g != null)
                <?php $game = \App\Game::where("id", "=", $g->game_id)->first(); ?>
                    @if ($game != null)
                    <li>
                        <a href="/ladder/{{ $ladder->abbreviation }}/games/{{ $game->id }}">
                            {{ "Game ID: " . $game->id . " - " . $game->scen }}
                        </a>
                    </li>
                    @endif
                @endif
                @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection