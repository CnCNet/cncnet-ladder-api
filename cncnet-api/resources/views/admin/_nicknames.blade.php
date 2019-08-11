<div class="usernames">
<?php $otherNames = \App\Player::where("user_id", $user->id)
    ->where("ladder_id", $ladderId)
    ->get();
?>

<?php
    $now = \Carbon\Carbon::now();
    $start = $now->startOfMonth()->toDateTimeString();
    $end = $now->endOfMonth()->toDateTimeString();
    $history = \App\LadderHistory::where("starts", $start)->where("ends", $end)
        ->where("ladder_id", $ladderId)
        ->first();
?>
<h5>Player nicknames:</h5> 
<ul>
    <?php foreach($otherNames as $otherName): ?>
    <li>
        <a href="/ladder/{{ $history->short }}/{{ $history->ladder->game }}/player/{{ $otherName->username}}">
            {{ $otherName->username }}
        </a>
        -- {{ $otherName->created_at->toDateString()}}
    </li>
    <?php endforeach; ?>
</ul>
</div>