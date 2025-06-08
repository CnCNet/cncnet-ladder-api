<div class="player-card player-box option {{ $hidden ? 'hidden' : 'new' }}" id="option_{{ $sov->id }}" style="margin-bottom: 8px">
    <form method="POST" action="optval">
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
        <input type="hidden" name="id" value="{{ $sov->id }}" />
        <input type="hidden" name="ladder_id" value="{{ $ladderId }}" />
        <input type="hidden" name="qm_map_id" value="{{ $qmMapId }}" />

        <div class="form-group">
            <label for="spawn_option_{{ $sov->id }}"> {{ $hidden ? '' : 'New' }} Option </label>
            <select name="spawn_option_id" class="form-control" id="spawn_option_{{ $sov->id }}">
                @foreach ($spawnOptions as $opt)
                    <option value="{{ $opt->id }}" {{ $opt->id == $sov->spawn_option_id ? 'selected' : '' }}>{{ $opt->name->string }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="spawn_value_{{ $sov->id }}"> Value </label>
            <input type="text" name="value" class="form-control" value="{{ $sov->value ? $sov->value->string : '' }}" id="spawn_value_{{ $sov->id }}" />
        </div>
        <button type="submit" name="update" class="btn btn-primary btn-md" value="1">{{ $button }}</button>
        @if ($sov->id != 'new')
            <button type="submit" name="update" class="btn btn-danger btn-md" value="2">Remove</button>
        @endif
    </form>
</div>
