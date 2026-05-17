@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Audit Log</h2>
    <form method="GET" action="">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="model_type">Model Type</label>
                <select name="model_type" id="model_type" class="form-control">
                    <option value="">All</option>
                    <option value="App\Models\Ban" @if(request('model_type')=='App\Models\Ban' ) selected @endif>Ban</option>
                    <option value="App\Models\Game" @if(request('model_type')=='App\Models\Game' ) selected @endif>Game</option>
                    <option value="App\Models\GameReport" @if(request('model_type')=='App\Models\GameReport' ) selected @endif>GameReport</option>
                    <option value="App\Models\Map" @if(request('model_type')=='App\Models\Map' ) selected @endif>Map</option>
                    <option value="App\Models\MapPool" @if(request('model_type')=='App\Models\MapPool' ) selected @endif>MapPool</option>
                    <option value="App\Models\PlayerGameReport" @if(request('model_type')=='App\Models\PlayerGameReport' ) selected @endif>PlayerGameReport</option>
                    <option value="App\Models\QmLadderRules" @if(request('model_type')=='App\Models\QmLadderRules' ) selected @endif>QmLadderRules</option>
                    <option value="App\Models\QmMap" @if(request('model_type')=='App\Models\QmMap' ) selected @endif>QmMap</option>
                    <option value="App\Models\SpawnOptionValue" @if(request('model_type')=='App\Models\SpawnOptionValue' ) selected @endif>SpawnOptionValue</option>
                    <option value="App\Models\User" @if(request('model_type')=='App\Models\User' ) selected @endif>User</option>
                    <option value="App\Models\UserSettings" @if(request('model_type')=='App\Models\UserSettings' ) selected @endif>UserSettings</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="event">Event Type</label>
                <select name="event" id="event" class="form-control">
                    <option value="">All</option>
                    <option value="created" @if(request('event')=='created' ) selected @endif>Created</option>
                    <option value="updated" @if(request('event')=='updated' ) selected @endif>Updated</option>
                    <option value="deleted" @if(request('event')=='deleted' ) selected @endif>Deleted</option>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Date</th>
                <th>Model Type</th>
                <th>Event</th>
                <th>Model ID</th>
                <th>User</th>
                <th>Description</th>
                <th>Changes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $activity)
            <tr>
                <td>{{ $activity->created_at }}</td>
                <td>{{ class_basename($activity->subject_type) }}</td>
                <td>{{ $activity->event }}</td>
                <td>{{ $activity->subject_id }}</td>
                <td>
                    @if($activity->causer)
                    {{ $activity->causer->name }}
                    @if($activity->causer->alias)
                    ({{ $activity->causer->alias }})
                    @endif
                    @else
                    System
                    @endif
                </td>
                <td>
                    @if($activity->description)
                        <strong>{{ $activity->description }}</strong>
                    @else
                        <em>-</em>
                    @endif
                </td>
                <td>
                    @if($activity->properties && isset($activity->properties['changes_summary']))
                        {{-- Custom reprocess format --}}
                        <div class="mb-2">
                            <strong>Changes:</strong>
                            <ul class="mb-0">
                                @foreach($activity->properties['changes_summary'] as $change)
                                    <li>{{ $change }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @if(isset($activity->properties['status']))
                            <small class="text-muted">Status: {{ $activity->properties['status'] }}</small>
                        @endif
                        <details class="mt-2">
                            <summary style="cursor: pointer;" class="text-primary">Show full details</summary>
                            <div class="mt-2">
                                @if(isset($activity->properties['before_state']))
                                    <strong>Before:</strong>
                                    <pre style="font-size: 0.8rem; max-height: 300px; overflow-y: auto;">{{ json_encode($activity->properties['before_state'], JSON_PRETTY_PRINT) }}</pre>
                                @endif
                                @if(isset($activity->properties['after_state']))
                                    <strong>After:</strong>
                                    <pre style="font-size: 0.8rem; max-height: 300px; overflow-y: auto;">{{ json_encode($activity->properties['after_state'], JSON_PRETTY_PRINT) }}</pre>
                                @endif
                            </div>
                        </details>
                    @elseif($activity->properties && isset($activity->properties['attributes']))
                        {{-- Standard Spatie format --}}
                        <strong>New:</strong>
                        <pre style="font-size: 0.8rem; max-height: 300px; overflow-y: auto;">{{ json_encode($activity->properties['attributes'], JSON_PRETTY_PRINT) }}</pre>
                        @if(isset($activity->properties['old']))
                            <strong>Old:</strong>
                            <pre style="font-size: 0.8rem; max-height: 300px; overflow-y: auto;">{{ json_encode($activity->properties['old'], JSON_PRETTY_PRINT) }}</pre>
                        @endif
                    @else
                        <em>No changes</em>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">No audit events found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    {{ $activities->withQueryString()->links() }}
</div>
@endsection