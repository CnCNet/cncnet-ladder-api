<?php
    $numOfCols = 4;
    $rowCount = 0;
    $column = 12 / $numOfCols;
?>

<div class="row recent-game">
    @foreach($games as $game)
        <div class="col-md-{{ $column }}">
            <?php $pp = $game->playerGameReports()->first(); ?>
            @include("components/game-box",
            [
                "url" => "/ladder/". $history->short . "/" . $history->ladder->abbreviation . "/games/" . $game->id,
                "game" => $history->ladder->abbreviation,
                "gamePlayers" => $game->playerGameReports(),
                "gameReport" => $game->report()->first(),
                "status" => isset($pp) ? $pp->won ? "won" : "lost" : "",
                "points" => $pp,
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