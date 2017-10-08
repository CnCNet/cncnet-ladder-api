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
        <ul class="text-center list-unstyled">
        <li> Players: {{ $game->plrs }} </li>
        </ul>
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
        <h3 class="text-center small">
        @foreach($game->allReports()->get() as $gameReport)
            <form action="/admin/games/switch" class="text-center" method="POST">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input name="game_id" type="hidden" value="{{ $game->id }}"/>
                <input name="game_report_id" type="hidden" value="{{ $gameReport->id }}" />
                <button type="submit" class="btn btn-md btn-danger" @if($gameReport->best_report) disabled @endif>Switch</button>
            </form>

            @foreach($gameReport->playerGameReports()->get() as $pgr)
            <?php $player = $pgr->player()->first() ?>

            <span class="player">
                {{ $player->username or "Unknown" }} +{{ $pgr->points or "" }}
                @if($pgr->won)
                    <i class="fa fa-trophy fa-fw" style="color: #E91E63;"></i>
                @else
                    <i class="fa fa-sun-o fa-fw" style="color: #00BCD4;"></i>
                @endif
            </span>
            @endforeach
        @endforeach
        </h3>
    </div>
</a>