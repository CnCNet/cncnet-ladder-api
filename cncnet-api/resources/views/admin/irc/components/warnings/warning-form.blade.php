<form method="POST"
    @if ($warning) action="{{ route('admin.irc.warnings.update') }}" @else action="{{ route('admin.irc.warnings.create') }}" @endif>

    @if ($warning)
        <input type="hidden" name="warning_id" value="{{ $warning->id }}" />
    @endif

    @csrf
    <p>
        You must enter at least one value for User or Ident.
    </p>

    <div class="input-group mb-3">
        <span class="input-group-text">Username</span>

        <input type="text" class="form-control" placeholder="e.g Ken" aria-label="Username" name="username"
            value="{{ old('username', $warning?->username) }}">

        <span class="input-group-text">Ident</span>

        <input type="text" class="form-control" placeholder="e.g 16c4dd" aria-label="Ident" name="ident"
            value="{{ old('ident', $warning?->ident) }}">
    </div>

    <div class="input-group mb-3">
        <p>
            Warning messages will automatically be prefixed with this message: <br /><strong>"This is an official warning from CnCNet. Please read the
                community guidelines and rules. https://cncnet.org/community-guidelines-and-rules - Reason for warning: (Reason Here)"</strong>
        </p>
        <span class="input-group-text">Keep your warning reason short.</span>
        <textarea class="form-control" aria-label="With textarea" name="warning_message" required>{{ old('warning_message', $warning?->warning_message) }}</textarea>
    </div>

    <div class="input-group mb-3">
        <label class="input-group-text" for="channel">Warning on a specific Channel</label>
        <select class="form-select" id="channel" name="channel" @if ($warning) readonly @endif>
            <option selected value="">- Select channel this warning should be applied to -</option>
            <option value="#cncnet" @if ($warning?->channel == '#cncnet') selected @endif>#cncnet</option>
            <option value="#cncnet-yr"@if ($warning?->channel == '#cncnet-yr') selected @endif>#cncnet-yr</option>
        </select>
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

    <button class="btn btn-primary" type="submit">Save warning</button>
</form>
