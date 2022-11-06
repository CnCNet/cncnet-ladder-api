<div class="nav-item">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
        <div class="icon">
            @include('icons.trophy', ['colour' => '#00ff8a'])
        </div>

        <span class="nav-item-text">
            Ladders
        </span>

        <span class="caret"></span>
    </a>

    <ul class="dropdown-menu" style="min-width:250px">
        <li role="separator" class="nav-title">C&amp;C Live Ladders</li>
        @foreach ($ladders as $history)
            <li>
                <a href="/ladder/{{ $history->short . '/' . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}">
                    {{ $history->ladder->name }}
                </a>
            </li>
        @endforeach


        @if (count($private_ladders) > 0)
            <li role="separator" class="divider"></li>
            <li role="separator" class="nav-title">Private Ladders
                <i class="fa fa-lock" aria-hidden="true"></i>
            </li>
        @endif

        @foreach ($private_ladders as $private)
            <li>
                <a href="/ladder/{{ $private->short . '/' . $private->ladder->abbreviation }}/" title="{{ $private->ladder->name }}">
                    {{ $private->ladder->name }}
                </a>
            </li>
        @endforeach

        @if (count($clan_ladders) > 0)
            <li role="separator" class="divider"></li>
            <li role="separator" class="nav-title">Clan Ladders
            </li>
        @endif

        @foreach ($clan_ladders as $history)
            <li>
                <a href="/clans/{{ $history->ladder->abbreviation . '/leaderboards/' . $history->short }}/" title="{{ $history->ladder->name }}">
                    {{ $history->ladder->name }}
                </a>
            </li>
        @endforeach

        <li role="separator" class="divider"></li>
        <li role="separator" class="nav-title">C&amp;C Ladder Champions</li>

        @foreach ($ladders as $history)
            <li>
                <a href="/ladder-champions/{{ $history->abbreviation }}/" title="{{ $history->ladder->name }}">
                    {{ $history->ladder->name }} Winners
                </a>
            </li>
        @endforeach
    </ul>
</div>
