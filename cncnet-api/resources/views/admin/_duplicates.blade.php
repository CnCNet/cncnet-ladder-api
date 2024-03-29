<div class="duplicate-accounts">
    <?php if ($user->ip_address_id != null) : ?>
    <h5 class="mt-2 mb-2">Duplicates/Shared accounts:</h5>

        <?php $ips = \App\Models\IpAddressHistory::where('ip_address_id', $user->ip_address_id)->get(); ?>

    <ul class="list-styled">
            <?php foreach ($ips as $ip): ?>
            <?php $u = \App\Models\User::where('id', $ip->user_id)
            ->where('id', '!=', $user->id)
            ->first();
            ?>
        @if ($u != null)
            <li>
                <a href="?userId={{ $u->id }}">{{ $u->name }}</a> - {{ $u->email }}
            </li>
        @endif
        <?php endforeach; ?>
    </ul>

    <h5 class="mt-2 mb-2">QM Client Ids:</h5>

        <?php $qmUserIds = \App\Models\QmUserId::where('user_id', $user->id)->get(); ?>
    <ul class="list-styled">
        @foreach ($qmUserIds as $qmUserId)
            <li>
                Client Id: {{ $qmUserId->qm_user_id }}
                <ul class="list-styled">
                    <li><strong>User: {{ $qmUserId->user->name }}</strong></li>
                    <li><strong>Created: {{ $qmUserId->created_at }}</li>
                    <li><strong>Updated: {{ $qmUserId->updated_at }}</li>
                </ul>
            </li>
        @endforeach
    </ul>

    <h5 class="mt-2 mb-2">Ip address:</h5>
    <ul class="list-styled">
            <?php foreach ($user->ipHistory as $ipHistory): ?>
        <li>
            <div>
                @if ($hostname == 'true')
                    <strong>Hostname: {{ gethostbyaddr($ipHistory->ipaddress->address) }}</strong>
                @endif
            </div>
            <a href="https://www.whoismyisp.org/ip/{{ $ipHistory->ipaddress->address }}" target="_blank">
                {{ $ipHistory->ipaddress->address }}
            </a>
            <label>{{ $ipHistory->created_at->toDateString() }}</label>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php endif; ?>
</div>
