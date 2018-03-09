<?php
    $numOfCols = 4;
    $rowCount = 0;
    $column = 12 / $numOfCols;
?>

<div class="row recent-game">
    @foreach($games as $game)
        <div class="col-md-{{ $column }}">
        
        <?php
        if($game->won){
        $status = "won";
        }else if($game->disconnected){
        $status = "dc";
        }else if($game->draw){
        $status = "draw";
        }else{
        $status = "lost";
        }
        ?>


        <?php $report = \App\PlayerGameReport::where('game_report_id', $game->game_report_id); ?>
        @include("components/game-box",
        [
            "url" => "/ladder/". $history->short . "/" . $history->ladder->abbreviation . "/games/" . $game->game_id,
            "game" => $history->ladder->abbreviation,
            "gamePlayers" => $report,
            "gameReport" => $report->first(),
            "status" => $status,
            "points" => $game,
            "map" => $game->hash,
            "title" => $game->scen,
            "date" => $game->created_at
        ])
        </div>
        <?php $rowCount++; ?>
        @if($rowCount % $numOfCols == 0)
        </div><div class="row recent-game">
        @endif
    @endforeach
</div>