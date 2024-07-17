<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Ident</th>
                <th>Host</th>
                <th>Reason</th>
                <th>Admin</th>
                <th>Channel </th>
                <th>Global Ban</th>
                <th>Created At</th>
                <th>Expires At </th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bans as $ban)
                <tr>
                    <td> {{ $ban->username }}</td>
                    <td> {{ $ban->ident }}</td>
                    <td> {{ $ban->host }}</td>
                    <td> {{ $ban->reason }}</td>
                    <td> {{ \App\Models\User::find($ban->admin_id)->name }}</td>
                    <td>{{ $ban->channel }}</td>
                    <td>{{ $ban->global_ban ? 'Yes' : 'No' }}</td>
                    <td>{{ $ban->created_at?->format('F j, Y, g:i a T') }}</td>
                    <td>{{ $ban->expires_at?->format('F j, Y, g:i a T') ?? 'Never' }} </td>
                    <td>
                        @if ($ban->expires_at?->isPast())
                            <div class="badge rounded-pill text-bg-info">Expired</div>
                        @else
                            <div class="badge rounded-pill text-bg-primary">Active</div>
                        @endif
                    </td>
                    <td><a href="{{ route('admin.irc.bans.edit', ['id' => $ban->id]) }}">View Ban</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
