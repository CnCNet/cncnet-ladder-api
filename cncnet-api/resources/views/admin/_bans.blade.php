
<div class="bans">
    <h5>Bans:</h5>

    <?php foreach($user->collectBans() as $ban):?>
        <div style="margin-bottom: 15px;">
        <ul class="list-styled">
            <li><strong>User ID:</strong> #{{ $ban->user_id }}</li>
            <li><strong>Internal note:</strong> {{ $ban->internal_note }}</li>
            <li><strong>Public reason:</strong> {{ $ban->plubic_reason }}</li>
            <li><strong>Expires:</strong> {{ $ban->expires->toDateString() }}</li>
            <li><strong>By:</strong> {{ $ban->admin->name }}</li>
        </ul>
        </div>
    <?php endforeach; ?>
</div>