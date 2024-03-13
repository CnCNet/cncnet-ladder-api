<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle d-flex align-items-center me-1 ms-1 ps-2 pe-4" href="#" data-bs-toggle="dropdown"
       aria-expanded="false">
        <span class="material-symbols-outlined icon">
            hotel_class
        </span>
        <span class="ps-2 ms-2 me-2 text text-uppercase">
            <span class="d-block d-xl-none">HOF</span>
            <span class="d-none d-xl-block">Hall of Fame</span>
        </span>
    </a>

    <ul class="dropdown-menu dropdown-menu-dark">

        <li>
            <h4 class="dropdown-header text-uppercase">Hall of Fame - 1 vs 1 Ladders</h4>
        </li>

        @foreach ($ladders as $history)
            <li>
                <a href="{{ \App\Models\URLHelper::getChampionsLadderUrl($history) }}"
                   title="{{ $history->ladder->name }}" class="dropdown-item">

                    <span class="d-flex align-items-center">
                        <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                        {{ $history->ladder->name }}
                    </span>
                </a>
            </li>
        @endforeach


        @if (isset($clan_ladders))
            @if (count($clan_ladders) > 0)
                <li>
                    <h4 class="dropdown-header text-uppercase">Hall of Fame - Clan Ladders</h4>
                </li>
            @endif

            @foreach ($clan_ladders as $history)
                @if (!$history->ladder->private)
                    <li>
                        <a href="{{ \App\Models\URLHelper::getChampionsLadderUrl($history) }}"
                           title="{{ $history->ladder->name }}" class="dropdown-item">
                            <span class="d-flex align-items-center">
                                <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                                {{ $history->ladder->name }}
                            </span>
                        </a>
                    </li>
                @endif
            @endforeach
        @endif
    </ul>
</li>
