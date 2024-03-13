<div class="achievement-tile {{ $cameo ? 'no-cameo' : '' }} {{ $unlocked == null ? 'achievement-locked' : 'achievement-unlocked' }} {{ \App\Models\AchievementTag::getAchievementNameByTag($tag) }}"
     data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-title="{{ $name }}"
     data-bs-content="{{ $description }}"
     data-bs-placement="top">

    <div class="d-flex w-100">
        <div class="achievement-image">
            @if ($cameo)
                <div class="cameo {{ $abbreviation }}-cameo cameo-tile cameo-{{ $cameo }}">
                </div>
            @endif
        </div>

        <div class="achievement-description">
            <div><strong>{{ $name }}</strong></div>
            <div>{{ $description }}</div>
            @if (isset($unlockedDate))
                @php
                    $date = new \Carbon\Carbon($unlockedDate);
                @endphp
                <div>Unlocked {{ $date->diffForHumans() }} </div>
            @endif
        </div>
    </div>

    @if ($unlocked == null)
        <div class="locked">
            <span class="material-symbols-outlined">
                lock
            </span>
        </div>
    @endif

    @if (isset($unlockedProgress))
        <div class="ms-2 me-2 d-flex align-items-center mt-2 mb-2 w-100" style="z-index:10">
            <div class="achievement-progress progress" style="width:150px;">
                <div class="progress-bar" role="progressbar" aria-label="Default striped example"
                     aria-valuenow="{{ $unlockedProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100"
                     style="width: {{ $unlockedProgress['percentage'] }}%">
                </div>
            </div>
            <small class="ms-1">{{ $unlockedProgress['unlockedCount'] }}/{{ $unlockedProgress['totalToUnlock'] }}
                unlocked</small>
        </div>
    @endif
</div>
