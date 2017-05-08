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
                    {{ $player->username }} <small>Player Statistics</small> 
                </h1>
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
                </ul>
                <ul class="list-inline">
                    <li>
                        Disconnects <strong>{{ $player->dc_count }}</strong>
                        <i class="fa fa-signal fa-fw"></i>
                    </li>
                </ul>
                <a href="/ladder/{{ $ladder->abbreviation }}/player/" class="btn btn-secondary btn-lg">All Players</a>

            </div>
        </div>
    </div>
</div>
@endsection


@section('content')
<section class="cncnet-features general-texture game-detail">
    <div class="container">
        <div class="row">
            <div class="col-md-9">
                
            </div>
            <div class="col-md-3">
                <div class="profile-rank text-right">
                <ul class="list-inline">
                    <li>
                        <p>Rank #1</p>
                    </li>
                    <li>
                        <i class='fa fa-trophy fa-fw fa-2x'></i>
                        Colonel
                    </li>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h3>Top Stats</h3>
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
                        FPS <strong>{{ $player->games_count }} </strong>
                        <i class="fa fa-diamond fa-fw"></i>
                    </li>               
                    <li>
                        Hours Played <strong>{{ $player->games_count }} </strong>
                        <i class="fa fa-diamond fa-fw"></i>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>
<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h3>Recent Games</h3>
                <div class="table-responsive">
                    <table class="table table-hover player-games">
                        <thead>
                        <tr>
                            <th>When &amp; Duration <i class="fa fa-clock-o fa-fw"></i></th>
                            <th>Players in game <i class="fa fa-user fa-fw"></i></th>
                            <th>Map played <i class="fa fa-map-marker fa-fw"></i></th>
                            <th>Game Settings <i class="fa fa-cog fa-fw"></i></th>
                            <th>Game Details <i class="fa fa-level-down fa-fw"></i></th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($player->games()->get() as $game)
                        <?php $g = \App\Game::find($game->game_id)->first(); ?>
                        <?php $stats = \App\Game::find($game->game_id)->stats()->get(); ?>

                        <tr>
                            <td>
                                {{ $g->created_at }} - {{ $g->duration }}
                            </td>
                            <td>
                                <ul class="list-unstyled">
                                @foreach($stats as $s)
                                    <?php $p = $s->player()->first(); ?>
                                        
                                    @if($p)
                                    <li>
                                        <a href="/ladder/{{ $ladder->abbreviation }}/player/{{$p->username}}">
                                            {{ $p->username }} 
                                          
                                            @if($s->cmp == 1) 
                                            <i class="fa fa-angle-double-up fa-lg" aria-hidden="true" style="color:green;"></i> 
                                            @else 
                                            <i class="fa fa-angle-double-down fa-lg" aria-hidden="true" style="color:red"></i> 
                                            @endif
                                        </a>
                                    </li>
                                    @endif
                                @endforeach
                                </ul>
                            </td>
                            <td>{{ \App\Map::find($g->map_id)->first()->name }}</td>
                            <td>
                                <ul class="list-unstyled">
                                    <li>Crates: {{ $g->crates ? "On": "Off" }}</li>
                                </ul>
                            </td>
                            <td>
                                <ul class="list-unstyled">
                                    <li>Average FPS: {{ $g->afps }}</li>
                                    <li>Bases: {{ $g->bases }}</li>
                                    <li>Out of Sync: {{ $g->oosy ? "Yes" : "No" }}</li>
                                </ul>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

