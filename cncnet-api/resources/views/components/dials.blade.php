<ul class="list-inline">
    <li>
    @if ($gamesWon > 0)
    <?php $winPercent = number_format($gamesWon / ($gamesWon + $gamesLost) * 100); ?>
    <div class="c100 p{{ $winPercent }} center big green">
        <p class="title">Winning</p>
        <p class="value">{{ $winPercent }}%</p>
        <div class="slice"><div class="bar"></div><div class="fill"></div></div>
    </div>
    @endif
    </li>
    <li>
        <div class="c100 p100 center big purple">
            <p class="title">Games</p>
            <p class="value"> {{ $gamesCount }}   <i class="fa fa-diamond fa-fw"></i></p>
            <div class="slice"><div class="bar"></div><div class="fill"></div></div>
        </div>
    </li>
    <li>
        <div class="c100 p{{ 100 - (60 - $player->average_fps) }} center big blue">
            <p class="title">Average FPS</p>
            <p class="value">{{ $averageFps }} <i class="fa fa-industry fa-fw"></i></p>
            <div class="slice"><div class="bar"></div><div class="fill"></div></div>
        </div>
    </li>
</ul>