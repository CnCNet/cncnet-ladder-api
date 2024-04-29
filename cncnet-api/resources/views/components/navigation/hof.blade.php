@foreach ($ladders as $history)
    <li>
        <a href="{{ \App\Models\URLHelper::getChampionsLadderUrl($history) }}" title="{{ $history->ladder->name }}" class="dropdown-item">

            <span class="d-flex align-items-center">
                <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                {{ $history->ladder->name }}
            </span>
        </a>
    </li>
@endforeach

@foreach ($clan_ladders as $history)
    @if (!$history->ladder->private)
        <li>
            <a href="{{ \App\Models\URLHelper::getChampionsLadderUrl($history) }}" title="{{ $history->ladder->name }}" class="dropdown-item">
                <span class="d-flex align-items-center">
                    <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                    {{ $history->ladder->name }}
                </span>
            </a>
        </li>
    @endif
@endforeach
