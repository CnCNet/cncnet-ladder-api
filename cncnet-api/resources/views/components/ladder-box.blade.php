<a href="{{ $url }}" class="game-box-ladder-link col-6 {{ $abbrev ?? $history->ladder->abbreviation }}"
    title="View {{ $history->ladder->name }}">
    <div class="game-box-ladder-link-bg bg-{{ $abbrev ?? $history->ladder->abbreviation }}"></div>
    <div class="ladder-description">
        <div class="logo">
            <img src="{{ \App\Models\URLHelper::getLadderLogoByAbbrev($abbrev ?? $history->ladder->abbreviation) }}"
                alt="{{ $history->ladder->name }}" />
        </div>
        <div class="text-center mt-2">
            <h4 class="fs-6">
                {{ $history->ladder->name }}
                @if ($history->ladder->ladder_type == \App\Models\Ladder::ONE_VS_ONE)
                    1vs1 Ladder
                @else
                    Ladder
                @endif
            </h4>
            <p class="text-uppercase fw-bold fs-6">
                {{ Carbon\Carbon::parse($history->starts)->format('F Y') }}
            </p>
        </div>
    </div>
</a>
