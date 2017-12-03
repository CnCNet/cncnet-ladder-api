<div class="game-box">
    <div class="preview" style="background-image:url(/images/maps/{{ $game }}/{{ $map or ""}}.png)">
        <a href="{{ $url or ""}}" class="status status-{{ $status }}"></a>
    </div>

    <a href="{{ $url or ""}}" class="game-box-link" data-toggle="tooltip" data-placement="top" title="View game">
        <div class="details text-center">
            <h4 class="title">{{ $title }}</h4>
            <?php $now = \Carbon\Carbon::now(); $days = $date->diffInDays($now); $hours = $date->diffInHours($now); $mins = $date->diffInMinutes($now);  ?>
            @if($hours >= 24)
            <small class="status text-capitalize">{{ $status . " " . $days . " days ago"}}</small>
            @elseif($hours <= 1)
            <small class="status text-capitalize">{{ $status . " " . $mins . " minutes ago"}}</small>
            @else
            <small class="status text-capitalize">{{ $status . " " . $hours . " hours ago"}}</small>
            @endif
        </div>
        @if ($points != null)
        <div class="footer text-center">
            <?php $opponent = $gamePlayers->where("player_id", "!=", $points->player_id)->first(); ?>
            <h5 class="player {{ $status or "lost"}}">
                {{ $points->player->username }} <span class="points">@if($points->points >= 0) +@endif{{ $points->points }}</span>
            </h5>
            <p class="vs">vs</p>
            @if ($opponent)
            <h5 class="player {{ $opponent->won ? "won" : "lost " }}">
                {{ $opponent->player->username }} <span class="points">@if($opponent->points >= 0) +@endif{{ $opponent->points }}</span>
            </h5>
            @endif
        </div>
        @endif
    </a>
</div>
