<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="material-symbols-outlined">
            military_tech
        </span>
        <span class="nav-item-text">
            Ladders
        </span>
    </a>
    <ul class="dropdown-menu dropdown-menu-dark">
        @foreach ($ladders as $history)
            <li>
                <a href="/ladder/{{ $history->short . '/' . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="dropdown-item">
                    {{ $history->ladder->name }}
                </a>
            </li>
        @endforeach

        @if (isset($private_ladders))
            @if (count($private_ladders) > 0)
                <li role="separator" class="divider"></li>
                <li role="separator" class="nav-title">Private Ladders
                    <i class="fa fa-lock" aria-hidden="true"></i>
                </li>
            @endif

            @foreach ($private_ladders as $private)
                <li>
                    <a href="/ladder/{{ $private->short . '/' . $private->ladder->abbreviation }}/" title="{{ $private->ladder->name }}" class="dropdown-item">
                        {{ $private->ladder->name }}
                    </a>
                </li>
            @endforeach
        @endif

        @if (isset($private_ladders))

            @if (count($clan_ladders) > 0)
                <li role="separator" class="divider"></li>
                <li role="separator" class="nav-title">Clan Ladders
                </li>
            @endif

            @foreach ($clan_ladders as $history)
                <li>
                    <a href="/clans/{{ $history->ladder->abbreviation . '/leaderboards/' . $history->short }}/" title="{{ $history->ladder->name }}" class="dropdown-item">
                        {{ $history->ladder->name }}
                    </a>
                </li>
            @endforeach
        @endif


        <li role="separator" class="divider"></li>
        <li role="separator" class="nav-title">C&amp;C Ladder Champions</li>

        @foreach ($ladders as $history)
            <li>
                <a href="/ladder-champions/{{ $history->abbreviation }}/" title="{{ $history->ladder->name }}" class="dropdown-item">
                    {{ $history->ladder->name }} Winners
                </a>
            </li>
        @endforeach
    </ul>
</li>
