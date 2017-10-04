@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-{{ $history->ladder->abbreviation }}.jpg
@endsection

@section('feature')
<div class="game">
    <div class="feature-background sub-feature-background">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1>
                        {{ $history->ladder->name }}
                    </h1>
                    <p>
                        <a href="/ladder" class="previous-link">
                            <i class="fa fa-caret-left" aria-hidden="true"></i>
                            <i class="fa fa-caret-left" aria-hidden="true"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="game">

    <section class="game-statistics">
        <div class="game-details">
            <div class="container" style="position:relative;padding: 60px 0;">
                @foreach($stats as $k => $stat)
                <?php $gameStats = \App\Stats::where("id", "=", $stat->stats_id)->first(); ?>
                <div class="hidden-xs faction faction-{{ $gameStats->faction($history->ladder->abbreviation, $gameStats->cty) }} @if($k == 0)faction-left @else faction-right @endif"></div>
                @endforeach

                <div class="row">
                    <div class="col-md-12">

                        <h3 class="game-intro text-center"> 
                        @foreach($stats as $k => $stat)
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

                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dark-texture">
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
                                    <strong>Funds Left: </strong> {{ $credits->value or "" }} 
                                </p>
                                <p class="colour player-panel-{{ $gameStats->colour($gameStats->col) }}" style="width:25px;height:25px;"></p>
                                <div class="country">
                                    <span class="flag-icon flag-icon-{{ $gameStats->country($gameStats->cty) }}"></span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="dark-texture">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h3>Game details</h3>
                    <ul class="list-unstyled">
                        <li><strong>Short Game:</strong> {{ $game->shrt ? "On" : "Off" }}</li>
                        <li><strong>Superweapons:</strong> {{ $game->supr ? "On" : "Off" }}</li>
                        <li><strong>Crates:</strong> {{ $game->crat ? "On" : "Off" }}</li>
                        <li><strong>Credits:</strong> {{ $game->cred }}</li>
                        <li><strong>Duration:</strong> {{ gmdate("H:i:s", $game->dura) }}</li>
                        <li><strong>MCV Redeploy:</strong> {{ $game->bamr & 1 ? "On" : "Off" }}</li>                 
                        <li><strong>Build off Ally Conyard:</strong> {{ $game->bamr & 2 ? "On" : "Off" }}</li>
                        <li><strong>Average FPS:</strong> {{ $game->afps }}</li>
                        <li><strong>Reconnection Error (OOS):</strong> {{ $game->oosy ? "Yes" : "No" }}</li>
                        <li><strong>Disconnect:</strong> {{ $game->sdfx ? "Yes" : "No" }}</li>
                        <li><strong>Unit Count Start:</strong> {{ $game->unit ? $game->unit : 0 }}</li>
                        <li><strong>Players in Game:</strong> {{ $game->plrs ? $game->plrs : 0 }}</li>
                    </ul>
                </div>

                <div class="col-md-6">
                    <h3>Map - {{ $game->scen }} </h3>
                    <?php $map = \App\Map::where("hash", "=", $game->hash)->first(); ?>
                    @if ($map)
                    <div class="feature-map">
                        <img src="/images/maps/{{ $history->ladder->abbreviation}}/{{ $map->hash . ".png" }}">
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection