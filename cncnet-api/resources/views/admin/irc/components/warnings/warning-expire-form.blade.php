<form method="POST" action="{{ route('admin.irc.warnings.expire', ['id' => $warning->id]) }}">
    @csrf

    <button class="btn btn-primary" type="submit">Expire warning</button>
</form>
