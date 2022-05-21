<div class="usernames">
    <?php $otherNames = \App\Models\Player::where('user_id', $user->id)->get(); ?>

    <?php
    $now = \Carbon\Carbon::now();
    $start = $now->startOfMonth()->toDateTimeString();
    $end = $now->endOfMonth()->toDateTimeString();
    $history = \App\Models\LadderHistory::where('starts', $start)
        ->where('ends', $end)
        ->first();
    ?>

    <h5>Player nicknames:</h5>
    <?php foreach($otherNames as $otherName): ?>
    <div class="player-nicknames">
        <i class="icon icon-game icon-{{ $otherName->ladder->abbreviation }}"></i>
        @if ($history)
            <a
                href="/ladder/{{ $history->short }}/{{ $otherName->ladder->abbreviation }}/player/{{ $otherName->username }}">
                {{ $otherName->username }}
            </a>
        @else
            {{ $otherName->username }}
        @endif
        {{ $otherName->created_at->toDateString() }}
    </div>
    <?php endforeach; ?>
</div>
