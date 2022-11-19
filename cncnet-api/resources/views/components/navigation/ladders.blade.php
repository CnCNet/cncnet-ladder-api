<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle d-flex align-items-center me-1 ms-1 ps-2 pe-4" href="#" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="material-symbols-outlined icon">
            military_tech
        </span>
        <span class="ms-2 me-2 text text-uppercase">
            Ladders
        </span>
    </a>

    <ul class="dropdown-menu dropdown-menu-dark">
        <li>
            <h4 class="dropdown-header text-uppercase">1 vs 1 Ladders</h4>
        </li>
        @foreach ($ladders as $history)
            <li>
                <a href="/ladder/{{ $history->short . '/' . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="dropdown-item">
                    <span class="d-flex align-items-center">
                        <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                        {{ $history->ladder->name }}
                    </span>
                </a>
            </li>
        @endforeach

        @if (isset($private_ladders))
            @if (count($private_ladders) > 0)
                <li>
                    <h4 class="dropdown-header text-uppercase">Private Ladders</h4>
                </li>
            @endif

            @foreach ($private_ladders as $private)
                <li>
                    <a href="/ladder/{{ $private->short . '/' . $private->ladder->abbreviation }}/" title="{{ $private->ladder->name }}" class="dropdown-item">
                        <span class="d-flex align-items-center">
                            <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                            {{ $private->ladder->name }}
                        </span>
                    </a>
                </li>
            @endforeach
        @endif

        @if (isset($private_ladders))

            @if (count($clan_ladders) > 0)
                <li>
                    <h4 class="dropdown-header text-uppercase">Clan Ladders</h4>
                </li>
            @endif

            @foreach ($clan_ladders as $history)
                <li>
                    <a href="/clans/{{ $history->ladder->abbreviation . '/leaderboards/' . $history->short }}/" title="{{ $history->ladder->name }}" class="dropdown-item">
                        <span class="d-flex align-items-center">
                            <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                            {{ $history->ladder->name }}
                        </span>
                    </a>
                </li>
            @endforeach
        @endif
        {{-- <li>
            <h4 class="dropdown-header text-uppercase">Hall of Fame</h4>
        </li>
        @foreach ($ladders as $history)
            <li>
                <a href="/ladder-champions/{{ $history->abbreviation }}/" title="{{ $history->ladder->name }}" class="dropdown-item">

                    <span class="d-flex align-items-center">
                        <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                        {{ $history->ladder->name }}
                    </span>
                </a>
            </li>
        @endforeach --}}
    </ul>
</li>
