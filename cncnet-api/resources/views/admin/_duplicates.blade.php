<div class="duplicate-accounts">
    @if ($user->ip_address_id != null)
        <h5 class="mt-2 mb-2">Duplicates/Shared accounts:</h5>
        <ul class="list-styled">
            @foreach ($ipDuplicates[$user->id] ?? [] as $dupe)
                <li>
                    <a href="?userId={{ $dupe->id }}">{{ $dupe->name }}</a> - {{ $dupe->email }}
                </li>
            @endforeach
        </ul>

        <h5 class="mt-2 mb-2">QM Client Ids:</h5>
        <ul class="list-styled">
            @foreach ($qmUserIds[$user->id] ?? [] as $qmUserId)
                <li>
                    Client Id: {{ $qmUserId->qm_user_id }}
                    <ul class="list-styled">
                        <li><strong>User: {{ $qmUserId->user->name }}</strong></li>
                        <li><strong>Created: {{ $qmUserId->created_at }}</strong></li>
                        <li><strong>Updated: {{ $qmUserId->updated_at }}</strong></li>
                    </ul>
                </li>
            @endforeach
        </ul>

        <h5 class="mt-2 mb-2">IP Addresses:</h5>
        <ul class="list-styled" id="ip-history-{{ $user->id }}">
            @foreach ($ipHistories[$user->id] ?? [] as $index => $ipHistory)
                <li class="ip-item {{ $index >= 10 ? 'd-none' : '' }}">
                    <div>
                        @if ($hostname == 'true')
                            <strong>Hostname: {{ gethostbyaddr($ipHistory->ipaddress->address) }}</strong>
                        @endif
                    </div>
                    <a href="https://www.whatismyisp.com/ip/{{ $ipHistory->ipaddress->address }}" target="_blank">
                        {{ $ipHistory->ipaddress->address }}
                    </a>
                    <label>{{ $ipHistory->created_at->toDateString() }}</label>
                </li>
            @endforeach
        </ul>

        @if (count($ipHistories[$user->id] ?? []) > 10)
            <button class="btn btn-secondary mt-2" onclick="showMoreIps('{{ $user->id }}')">Show More IP's</button>
        @endif
    @endif
</div>

<script>
    function showMoreIps(userId) {
        const container = document.getElementById(`ip-history-${userId}`);
        const hiddenItems = container.querySelectorAll('.ip-item.d-none');
        let shown = 0;
        hiddenItems.forEach((item, index) => {
            if (shown < 10) {
                item.classList.remove('d-none');
                shown++;
            }
        });

        if (container.querySelectorAll('.ip-item.d-none').length === 0) {
            event.target.style.display = 'none';
        }
    }
</script>
