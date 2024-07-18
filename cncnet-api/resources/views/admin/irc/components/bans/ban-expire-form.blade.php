<form method="POST" action="{{ route('admin.irc.bans.expire') }}">
    @csrf
    <input type="hidden" name="ban_id" value="{{ $ban->id }}" />
    <button class="btn btn-primary" type="submit">Expire ban</button>
</form>
