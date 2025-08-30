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
                    <option value="App\\Models\\Ban" @if(request('model_type')=='App\\Models\\Ban' ) selected @endif>Ban</option>
                    <option value="App\\Models\\Map" @if(request('model_type')=='App\\Models\\Map' ) selected @endif>Map</option>
                    <option value="App\\Models\\MapPool" @if(request('model_type')=='App\\Models\\MapPool' ) selected @endif>MapPool</option>
                    <option value="App\\Models\\QmLadderRules" @if(request('model_type')=='App\\Models\\QmLadderRules' ) selected @endif>QmLadderRules</option>
                    <option value="App\\Models\\QmMap" @if(request('model_type')=='App\\Models\\QmMap' ) selected @endif>QmMap</option>
                    <option value="App\\Models\\SpawnOptionValue" @if(request('model_type')=='App\\Models\\SpawnOptionValue' ) selected @endif>SpawnOptionValue</option>
                    <option value="App\\Models\\User" @if(request('model_type')=='App\\Models\\User' ) selected @endif>User</option>
                    <option value="App\\Models\\UserSettings" @if(request('model_type')=='App\\Models\\UserSettings' ) selected @endif>UserSettings</option>
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
                        @if($activity->properties && isset($activity->properties['attributes']))
                            <strong>New:</strong>
                            <pre>{{ json_encode($activity->properties['attributes'], JSON_PRETTY_PRINT) }}</pre>
                            @if(isset($activity->properties['old']))
                                <strong>Old:</strong>
                                <pre>{{ json_encode($activity->properties['old'], JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        @else
                            <em>No changes</em>
                        @endif
                    </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">No audit events found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    {{ $activities->withQueryString()->links() }}
</div>
@endsection