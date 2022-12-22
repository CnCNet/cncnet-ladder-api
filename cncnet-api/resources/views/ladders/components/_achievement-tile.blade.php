<div class="achievement-tile {{ $cameo ? 'no-cameo' : '' }} {{ $unlocked == null ? 'achievement-locked' : 'achievement-unlocked' }} {{ \App\AchievementTag::getAchievementNameByTag($tag) }}"
    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-title="{{ $name }}" data-bs-content="{{ $description }}">
    <div class="achievement-image">
        @if ($cameo)
            <div class="cameo {{ $abbreviation }}-cameo cameo-tile cameo-{{ $cameo }}">
            </div>
        @endif
    </div>

    <div class="achievement-description">
        <div><strong>{{ $name }}</strong></div>
        <div>{{ $description }}</div>
    </div>

    @if ($unlocked == null)
        <div class="locked">
            <span class="material-symbols-outlined">
                lock
            </span>
        </div>
    @endif
</div>
