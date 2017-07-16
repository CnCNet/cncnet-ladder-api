<?php
    $numOfCols = 4;
    $rowCount = 0;
    $bootstrapColWidth = 12 / $numOfCols;
?>

<h3>Recent Games</h3>
<div class="recent-games">

    <div class="row">
    @foreach($games as $game)
              
        <div class="col-md-{{ $bootstrapColWidth }}">
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
                                            
                        @if ($points != null)
                        <span class="player">
                            {{ $player->username or "Unknown" }} +{{ $points->points_awarded or "" }}
                            @if($points->game_won) 
                                <i class="fa fa-trophy fa-fw" style="color: #E91E63;"></i> 
                            @else 
                                <i class="fa fa-sun-o fa-fw" style="color: #00BCD4;"></i> 
                            @endif
                        </span>
                        @endif
                    @endforeach
                    </h3>
                    <div class="text-center date">
                        <?php 
                            $now = \Carbon\Carbon::now();
                            $days = $game->created_at->diffInDays($now); 
                            $hours = $game->created_at->diffInHours($now);
                            $minutes = $game->created_at->diffInMinutes($now);
                        ?>
                        @if ($days > 0)
                        {{ $days . " " . str_plural("day", $days) . " ago" }}
                        @elseif ($days == 0)
                        {{ $hours . " " . str_plural("hour", $hours) . " ago" }}
                        @elseif ($hours == 0)
                        {{ $minutes . " " . str_plural("minute", $minutes) . " ago" }}
                        @endif
                    </div>
                </div>
            </a>
        </div>

        <?php $rowCount++; ?>
        @if($rowCount % $numOfCols == 0)
        </div><div class="row">
        @endif
    @endforeach
    </div>
</div>