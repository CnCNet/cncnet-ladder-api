{{-- One row of the contribution bars: a label, a bar split between $parts by value, and the total. --}}
@php $sum = $parts->sum('value'); @endphp
<div class="ri-carry-row">
    <span class="ri-carry-label">{{ $label }}</span>
    <div class="ri-carry-bar">
        @if ($sum > 0)
            @foreach ($parts as $part)
                @if ($part['value'] > 0)
                    @php $share = $part['value'] / $sum; @endphp
                    <div class="ri-carry-segment ri-colour-{{ $part['colour'] }}"
                        style="width: {{ round($share * 100, 2) }}%"
                        title="{{ $part['name'] }}: {{ number_format($part['value']) }} ({{ round($share * 100) }}%)">
                        <span>{{ $part['name'] }} {{ round($share * 100) }}%</span>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
    <span class="ri-carry-total">{{ number_format($sum) }}</span>
</div>
