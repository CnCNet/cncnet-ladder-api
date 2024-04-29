@foreach ($ladders as $history)
    <li>
        <a href="{{ \App\Models\URLHelper::getLadderUrl($history) }}" title="{{ $history->ladder->name }}" class="dropdown-item">
            <span class="d-flex align-items-center">
                <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                {{ $history->ladder->name }}
            </span>
        </a>
    </li>
@endforeach

@if (isset($clan_ladders))
    @foreach ($clan_ladders as $history)
        @if (!$history->ladder->private)
            <li>
                <a href="{{ \App\Models\URLHelper::getLadderUrl($history) }}" title="{{ $history->ladder->name }}" class="dropdown-item">
                    <span class="d-flex align-items-center">
                        <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                        {{ $history->ladder->name }}
                    </span>
                </a>
            </li>
        @endif
    @endforeach
@endif

@if (isset($private_ladders))
    @if (count($private_ladders) > 0)
        <li>
            <h4 class="dropdown-header text-uppercase">Private Ladders</h4>
        </li>
    @endif

    @foreach ($private_ladders as $private)
        <li>
            <a href="{{ \App\Models\URLHelper::getLadderUrl($history) }}" title="{{ $private->ladder->name }}" class="dropdown-item">
                <span class="d-flex align-items-center">
                    <span class="me-3 icon-game icon-{{ $history->ladder->abbreviation }}"></span>
                    {{ $private->ladder->name }}
                </span>
            </a>
        </li>
    @endforeach
@endif
