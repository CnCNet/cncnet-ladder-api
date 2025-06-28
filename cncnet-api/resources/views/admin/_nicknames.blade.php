<div class="usernames">
    <h5>Player nicknames:</h5>

    @foreach ($playerNicknames[$user->id] ?? [] as $otherName)
        <div class="player-nicknames">
            <i class="icon icon-game icon-{{ $otherName->ladder->abbreviation }}"></i>
            <a href="/ladder/{{ $ladderHistory->short }}/{{ $otherName->ladder->abbreviation }}/player/{{ $otherName->username }}">
                {{ $otherName->username }}
            </a>
            {{ $otherName->created_at->toDateString() }}
        </div>
    @endforeach
</div>
