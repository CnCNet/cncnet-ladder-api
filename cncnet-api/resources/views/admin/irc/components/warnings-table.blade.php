<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Ident</th>
                <th>Warning Message</th>
                <th>Admin</th>
                <th>Channel</th>
                <th>Created At</th>
                <th>User Aknowledged</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($warnings as $warning)
                <tr>
                    <td> {{ $warning->username }}</td>
                    <td> {{ $warning->ident }}</td>
                    <td> {{ $warning->warning_message }}</td>
                    <td> {{ \App\Models\User::find($warning->admin_id)->name }}</td>
                    <td>{{ $warning->channel }}</td>
                    <td>{{ $warning->created_at?->format('F j, Y, g:i a T') }}</td>
                    <td>{{ $warning->aknowledged ? 'Yes' : 'No' }} </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
