@if (isset($duplicatesByUser[$user->id]))
    @php
        $dupes = $duplicatesByUser[$user->id];
    @endphp

    <div class="mt-3">
        @if ($dupes['confirmed']->isNotEmpty())
            <h5>Confirmed duplicates:</h5>
            <ul>
                @foreach ($dupes['confirmed'] as $entry)
                    <li>
                        <a href="?userId={{ $entry['user']->id }}">{{ $entry['user']->alias ?: $entry['user']->name }} - {{ $entry['user']->email }} </a>
                        @if ($entry['user']->isConfirmedPrimary())
                            <strong>(Primary account)</strong>
                        @endif
                        <span class="status-box confirmed">{{ $entry['reason'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($dupes['unconfirmed']->isNotEmpty())
            <h5>Possible duplicates:</h5>
            <ul>
                @foreach ($dupes['unconfirmed'] as $entry)
                    <li>
                        <a href="?userId={{ $entry['user']->id }}">{{ $entry['user']->alias ?: $entry['user']->name }} - {{ $entry['user']->email }} </a>
                        <span class="status-box unconfirmed">{{ $entry['reason'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($dupes['rejected']->isNotEmpty())
            <h5>Rejected duplicates:</h5>
            <ul>
                @foreach ($dupes['rejected'] as $entry)
                    <li>
                        <a href="?userId={{ $entry['user']->id }}">{{ $entry['user']->alias ?: $entry['user']->name }} - {{ $entry['user']->email }} </a>
                        <span class="status-box rejected">{{ $entry['reason'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

<div>
    @if ($user->ip_address_id != null)
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

<style>
    .status-box {
        display: inline-block;
        padding: 1px 10px;
        border-radius: 999px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .status-box.confirmed {
        background-color: #14532d; /* dark green */
        color: #bbf7d0; /* light green */
    }

    .status-box.unconfirmed {
        background-color: #2e2e2e; /* dark gray */
        color: #cfcfcf; /* light gray */
    }

    .status-box.rejected {
        background-color: #7f1d1d; /* dark red */
        color: #fca5a5; /* light red */
    }
</style>