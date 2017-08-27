<div class="game-box">
    <div class="preview" style="background-image:url(/images/maps/{{ $game }}/{{ $map->hash or ""}}.png)">
        <a href="#" class="status status-{{ $status or "lost"}}"></a>
    </div>

    <a href="{{ $url or ""}}" class="game-box-link">
        <div class="details text-center">
            <h4 class="title">{{ $title }}</h4>
            <?php $now = \Carbon\Carbon::now(); $days = $date->diffInDays($now); ?>
            <small class="status text-capitalize">{{ $status . " " . $days . " days ago"}}</small>
        </div>
        <div class="footer text-center">
            <?php $opponent = \App\PlayerPoint::where("game_id", "=", $points->game_id)->where("player_id", "!=", $points->player_id)->first(); ?>
            <h5 class="player {{ $status or "lost"}}">
                {{ $points->player->username }} <span class="points">+{{ $points->points_awarded }}</span>
            </h5>
            <p class="vs">vs</p>
            @if ($opponent)
            <h5 class="player {{ $status == "won" ? "lost" : "won" }}">
                {{ $opponent->player->username }} <span class="points">+{{ $opponent->points_awarded }}</span>
            </h5>
            @endif
        </div>
    </a>
</div>