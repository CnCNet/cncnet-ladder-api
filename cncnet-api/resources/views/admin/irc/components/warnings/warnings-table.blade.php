<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Ident</th>
                <th>Admin</th>
                <th>Channel</th>
                <th>Created At</th>
                <th>User Aknowledged</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($warnings as $warning)
                <tr>
                    <td> {{ $warning->username }}</td>
                    <td> {{ $warning->ident }}</td>
                    <td> {{ \App\Models\User::find($warning->admin_id)->name }}</td>
                    <td>{{ $warning->channel }}</td>
                    <td>{{ $warning->created_at?->format('F j, Y, g:i a T') }}</td>
                    <td>
                        @if ($warning->acknowledged)
                            <div class="badge rounded-pill text-bg-primary">Yes</div>
                        @else
                            <div class="badge rounded-pill text-bg-info">No</div>
                        @endif
                    </td>
                    <td>
                        @if ($warning->expired)
                            <div class="badge rounded-pill text-bg-info">Expired</div>
                        @else
                            <div class="badge rounded-pill text-bg-primary">Active</div>
                        @endif
                    </td>
                    <td><a href="{{ route('admin.irc.warnings.edit', ['id' => $warning->id]) }}" class="btn btn-primary btn-sm">View warning</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
