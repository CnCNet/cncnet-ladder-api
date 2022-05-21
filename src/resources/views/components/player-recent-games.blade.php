<?php
$numOfCols = 4;
$rowCount = 0;
$column = 12 / $numOfCols;
?>

<div class="row recent-game">
    @foreach ($games as $game)
        <div class="col-md-{{ $column }}">

            <?php
            if ($game->won) {
                $status = 'won';
            } elseif ($game->disconnected) {
                $status = 'dc';
            } elseif ($game->draw) {
                $status = 'draw';
            } else {
                $status = 'lost';
            }
            ?>

            <?php $pgr = \App\Models\PlayerGameReport::where('game_report_id', $game->game_report_id); ?>
            <?php $gr = \App\Models\GameReport::where('id', $game->game_report_id)->first(); ?>

            @include('components/game-box', [
                'url' => '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/games/' . $game->game_id,
                'game' => $history->ladder->abbreviation,
                'gamePlayers' => $pgr,
                'gameReport' => $gr,
                'status' => $status,
                'points' => $game,
                'map' => $game->hash,
                'title' => $game->scen,
                'date' => $game->created_at,
            ])
        </div>
        <?php $rowCount++; ?>
        @if ($rowCount % $numOfCols == 0)
</div>
<div class="row recent-game">
    @endif
    @endforeach
</div>
