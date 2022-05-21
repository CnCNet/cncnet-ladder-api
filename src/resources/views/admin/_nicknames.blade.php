<div class="usernames">
    <?php $otherNames = \App\Player::where('user_id', $user->id)->get(); ?>

    <?php
    $now = \Carbon\Carbon::now();
    $start = $now->startOfMonth()->toDateTimeString();
    $end = $now->endOfMonth()->toDateTimeString();
    $history = \App\LadderHistory::where('starts', $start)
        ->where('ends', $end)
        ->first();
    ?>

    <h5>Player nicknames:</h5>
    <?php foreach($otherNames as $otherName): ?>
    <div class="player-nicknames">
        <i class="icon icon-game icon-{{ $otherName->ladder->abbreviation }}"></i>
        <a
            href="/ladder/{{ $history->short }}/{{ $otherName->ladder->abbreviation }}/player/{{ $otherName->username }}">
            {{ $otherName->username }}
        </a>
        {{ $otherName->created_at->toDateString() }}
    </div>
    <?php endforeach; ?>
</div>
