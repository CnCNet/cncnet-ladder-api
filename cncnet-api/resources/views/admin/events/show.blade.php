@extends('layouts.app')
@section('title', 'Event admin')

@php
    $base = '/admin/events/' . $event->slug;
    $tabs = ['groups' => 'Groups', 'results' => 'Manual & Overrides', 'candidates' => 'Candidates', 'lifecycle' => 'Lifecycle'];
    $statusLabels = ['upcoming' => 'Upcoming', 'live' => 'Live', 'pending_final' => 'Awaiting final results', 'final' => 'Final'];
@endphp

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">{{ $def->name }}</x-slot>
        <x-slot name="description">Event admin</x-slot>
    </x-hero-with-video>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                <li class="breadcrumb-item active">{{ $def->name }}</li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4 mt-4 mb-5">
        <div class="container">
            @include('components.form-messages')

            <p>
                <a href="/events/{{ $event->slug }}" target="_blank">View public dashboard</a>
                · Status: <strong>{{ $statusLabels[$status] ?? $status }}</strong>
                · Last computed: {{ $event->computed_at ? $event->computed_at->toDateTimeString() . ' UTC' : 'never' }}
            </p>

            <ul class="nav nav-tabs mb-4">
                @foreach ($tabs as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ $base }}?tab={{ $key }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>

            {{-- Groups --}}
            @if ($tab === 'groups')
                <form method="GET" action="{{ $base }}" class="row g-2 mb-4">
                    <input type="hidden" name="tab" value="groups">
                    <div class="col-auto">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="User id, alias or name">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-secondary">Search users</button>
                    </div>
                </form>

                @if ($search !== '')
                    <h5>Search results</h5>
                    <table class="table table-dark table-sm mb-4">
                        <thead><tr><th>User id</th><th>Alias</th><th>Name</th><th>Primary</th><th>Add to group</th></tr></thead>
                        <tbody>
                            @forelse ($searchResults as $u)
                                <tr>
                                    <td>{{ $u->id }}</td>
                                    <td>{{ $u->alias }}</td>
                                    <td>{{ $u->name }}</td>
                                    <td>{{ $u->primary_user_id ? '#' . $u->primary_user_id : '' }}</td>
                                    <td>
                                        <form method="POST" action="{{ $base }}/groups" class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="user_id" value="{{ $u->id }}">
                                            <select name="group_key" class="form-select form-select-sm">
                                                @foreach ($def->groups as $g)
                                                    <option value="{{ $g }}">{{ $g }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-primary">Add</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No users found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif

                <div class="row g-3">
                    @foreach ($groups as $groupKey => $members)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card bg-dark text-light border-secondary h-100">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $groupKey }}</h5>
                                    @forelse ($members as $m)
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>
                                                @if ($m['missing'])
                                                    <span class="badge bg-danger">deleted user</span> #{{ $m['user_id'] }}
                                                @else
                                                    {{ $m['display'] }}
                                                    <span class="text-muted small">#{{ $m['user_id'] }}{{ $m['user_key'] !== $m['user_id'] ? ' → key ' . $m['user_key'] : '' }}</span>
                                                @endif
                                            </span>
                                            <form method="POST" action="{{ $base }}/groups">
                                                @csrf
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="group_key" value="{{ $groupKey }}">
                                                <input type="hidden" name="user_id" value="{{ $m['user_id'] }}">
                                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="text-muted small mb-0">No members</p>
                                    @endforelse

                                    <form method="POST" action="{{ $base }}/groups" class="d-flex gap-2 mt-3">
                                        @csrf
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="group_key" value="{{ $groupKey }}">
                                        <input type="number" name="user_id" class="form-control form-control-sm" placeholder="User id" required>
                                        <button class="btn btn-sm btn-primary">Add</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Manual results & overrides --}}
            @if ($tab === 'results')
                <form method="POST" action="{{ $base }}/results" class="row g-2 mb-4">
                    @csrf
                    <div class="col-12 col-md-4">
                        <label class="form-label">Award</label>
                        <select name="award_key" class="form-select" required>
                            @foreach ($def->cardAwards() as $award)
                                <option value="{{ $award->key }}" {{ old('award_key') === $award->key ? 'selected' : '' }}>
                                    {{ $award->title }} ({{ \App\Http\Controllers\Admin\EventAdminController::resultKind($award) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Winner user id</label>
                        <input type="number" name="user_id" value="{{ old('user_id') }}" class="form-control" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Game id (optional)</label>
                        <input type="number" name="game_id" value="{{ old('game_id') }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" value="{{ old('note') }}" maxlength="255" class="form-control">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Save</button>
                        <span class="small text-muted ms-2">Manual awards store a winner; computed awards store an override shown instead of the computed leader.</span>
                    </div>
                </form>

                <table class="table table-dark table-sm">
                    <thead><tr><th>Award</th><th>Kind</th><th>Winner</th><th>Game</th><th>Note</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($results as $r)
                            <tr>
                                <td>{{ $def->award($r->award_key)?->title ?? $r->award_key }}</td>
                                <td>{{ $r->kind }}</td>
                                <td>{{ $r->user?->alias ?: $r->user?->name }} <span class="text-muted small">#{{ $r->user_id }}</span></td>
                                <td>{{ $r->game_id }}</td>
                                <td>{{ $r->note }}</td>
                                <td>
                                    <form method="POST" action="{{ $base }}/results/{{ $r->award_key }}/{{ $r->kind }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Clear</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No manual results or overrides</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif

            {{-- Candidates --}}
            @if ($tab === 'candidates')
                @foreach ($def->cardAwards() as $award)
                    @if ($award->candidates !== null)
                        @php $list = $candidates[$award->key] ?? []; @endphp
                        <h4 class="mt-4">{{ $award->title }} <span class="small text-muted">selected by {{ $award->selectorLabel }} · {{ count($list) }} candidate wins</span></h4>
                        <table class="table table-dark table-sm" data-candidates="{{ $award->key }}">
                            <thead><tr><th>Date</th><th>Winner</th><th>Partner</th><th>Opponents</th><th>Map</th><th>Game</th></tr></thead>
                            <tbody>
                                @forelse ($list as $c)
                                    <tr>
                                        <td>{{ $c['played_at'] }}</td>
                                        <td>{{ $c['name'] }} <span class="text-muted small">key {{ $c['user_key'] }}</span></td>
                                        <td>{{ $c['partner'] }}</td>
                                        <td>{{ implode(', ', $c['opponents']) }}</td>
                                        <td>{{ $c['map'] }}</td>
                                        <td><a href="{{ $c['url'] }}" target="_blank">#{{ $c['game_id'] }}</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">No candidate wins yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                @endforeach
            @endif

            {{-- Lifecycle --}}
            @if ($tab === 'lifecycle')
                <dl class="row">
                    <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $statusLabels[$status] ?? $status }}</dd>
                    <dt class="col-sm-3">Window</dt><dd class="col-sm-9">{{ $def->starts->toDateTimeString() }} – {{ $def->ends->toDateTimeString() }} UTC</dd>
                    <dt class="col-sm-3">Last computed</dt><dd class="col-sm-9">{{ $event->computed_at ? $event->computed_at->toDateTimeString() . ' UTC' : 'never' }}</dd>
                    <dt class="col-sm-3">Finalised</dt><dd class="col-sm-9">{{ $event->finalised_at ? $event->finalised_at->toDateTimeString() . ' UTC by user #' . $event->finalised_by : 'no' }}</dd>
                </dl>

                <div class="d-flex gap-2">
                    <form method="POST" action="{{ $base }}/recompute">
                        @csrf
                        <button class="btn btn-secondary">Recompute now</button>
                    </form>
                    @if ($status === 'final')
                        <form method="POST" action="{{ $base }}/unfinalise" onsubmit="return confirm('Un-finalise and serve live results again?')">
                            @csrf
                            <button class="btn btn-outline-warning">Un-finalise</button>
                        </form>
                    @else
                        <form method="POST" action="{{ $base }}/finalise" onsubmit="return confirm('Freeze the current results?')">
                            @csrf
                            <button class="btn btn-danger" {{ $status !== 'pending_final' ? 'disabled' : '' }}>Finalise</button>
                        </form>
                    @endif
                </div>
                @if ($status !== 'pending_final' && $status !== 'final')
                    <p class="small text-muted mt-2">Finalise becomes available after the event ends.</p>
                @endif
            @endif
        </div>
    </section>
@endsection
