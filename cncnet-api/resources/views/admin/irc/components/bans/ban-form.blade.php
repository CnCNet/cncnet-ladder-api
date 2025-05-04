<form method="POST"
    @if ($ban) action="{{ route('admin.irc.bans.update') }}" @else action="{{ route('admin.irc.bans.create') }}" @endif>

    @if ($ban)
        <input type="hidden" name="ban_id" value="{{ $ban->id }}" />
    @endif

    @csrf
    <p>
        You must enter at least one value for User, Ident or Host. If one type is left blank, the bot will not ban by this field.
    </p>

    <div class="input-group mb-3">
        <span class="input-group-text">Username</span>

        <input type="text" class="form-control" placeholder="e.g Ken" aria-label="Username" name="username"
            value="{{ old('username', $ban?->username) }}" @if ($ban?->username) readonly @endif>

        <span class="input-group-text">Ident</span>

        <input type="text" class="form-control" placeholder="e.g 16c4dd" aria-label="Ident" name="ident" value="{{ old('ident', $ban?->ident) }}"
            @if ($ban?->ident) readonly @endif>

        <span class="input-group-text">Host</span>

        <input type="text" class="form-control" placeholder="e.g. gamesurge-d86aec00.revip2.asianet.co.th" aria-label="Host" name="host"
            value="{{ old('host', $ban?->host) }}" @if ($ban?->host) readonly @endif>
    </div>

    <div class="input-group mb-3">
        <span class="input-group-text">Reason for the ban</span>
        <textarea class="form-control" aria-label="With textarea" name="ban_reason" required>{{ old('ban_reason', $ban?->ban_reason) }}</textarea>
    </div>

    <div class="input-group mb-3">
        <label class="input-group-text" for="channel">Ban on a specific Channel</label>
        <select class="form-select" id="channel" name="channel">
            <option selected value="">- Select channel this ban should be applied to -</option>
            <option value="#cncnet" @if ($ban?->channel == '#cncnet') selected @endif>#cncnet</option>
            <option value="#cncnet-yr"@if ($ban?->channel == '#cncnet-yr') selected @endif>#cncnet-yr</option>
        </select>

        <span class="input-group-text">OR</span>

        <div class="input-group-text">
            <input class="form-check-input mt-0" type="checkbox" aria-label="Checkbox for following text input" id="banAllChannels" name="global_ban"
                @if ($ban?->global_ban != null) checked @endif>
        </div>

        <label class="input-group-text" for="banAllChannels" name="banAllChannels">Ban on all CnCNet Channels</label>
    </div>

    <div class="input-group mb-3">
        <span class="input-group-text">When will the ban expire? (Leave blank for never)</span>
        <input type="datetime-local" class="form-control" placeholder="" aria-label="Host" name="expires_at"
            value="{{ $ban?->expires_at?->format('Y-m-d\TH:i') }}">
    </div>

    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <button class="btn btn-primary" type="submit">Save ban</button>
</form>
