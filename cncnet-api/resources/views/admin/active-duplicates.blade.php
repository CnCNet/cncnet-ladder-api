@extends('layouts.app')
@section('title', 'Potential Active Duplicates')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev($ladder->abbreviation) }}">
        <x-slot name="title">Potential Active Duplicates</x-slot>
        <x-slot name="description">
            Potential duplicate accounts among active players
        </x-slot>

        <div class="mini-breadcrumb d-none d-lg-flex">
            <div class="mini-breadcrumb-item">
                <a href="/" class="">
                    <span class="material-symbols-outlined">
                        home
                    </span>
                </a>
            </div>
            <div class="mini-breadcrumb-item">
                <a href="/admin" class="">
                    <span class="material-symbols-outlined">
                        admin_panel_settings
                    </span>
                </a>
            </div>
        </div>
    </x-hero-with-video>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/admin">
                        <span class="material-symbols-outlined pe-3">
                            admin_panel_settings
                        </span>
                        Admin
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Potential Duplicates</li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4 mt-5 mb-5">

        <div class="container">
            <h2 class="mb-4">Suspected Duplicate Users</h2>

            <div class="mb-3">
                <form method="get" class="d-inline">
                    <input type="hidden" name="period" value="current">
                    <button type="submit" class="btn btn-sm {{ $period === 'current' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Current month
                    </button>
                </form>

                <form method="get" class="d-inline ms-2">
                    <input type="hidden" name="period" value="last">
                    <button type="submit" class="btn btn-sm {{ $period === 'last' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Last month
                    </button>
                </form>
            </div>

            <div class="mb-3">
                <div class="btn-group mb-2">
                    @foreach($activeLadders as $ladderOption)
                        <form method="get" class="me-2 d-inline">
                            <input type="hidden" name="abbreviation" value="{{ $ladderOption->abbreviation }}">
                            <input type="hidden" name="period" value="{{ $period }}">
                            <button type="submit" class="btn btn-sm {{ $selectedAbbreviation === $ladderOption->abbreviation ? 'btn-primary' : 'btn-outline-secondary' }}">
                                {{ $ladderOption->name }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            @if(count($potentialDuplicates) === 0)
                <p class="text-muted">No suspicious duplicate activity detected for this period.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Player</th>
                                <th>User ID</th>
                                <th>Alias</th>
                                <th>Suspected Duplicates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($potentialDuplicates as $entry)
                                <tr>
                                    <td>{{ $entry['player']->username }}</td>
                                    <td>#{{ $entry['user']->id }}</td>
                                    <td>{{ $entry['user']->alias ?? '—' }}</td>
                                    <td>
                                        <ul class="mb-0 ps-3">
                                            @foreach($entry['dupes'] as $dupeEntry)
                                                <li>
                                                    #{{ $dupeEntry['user']->id }} — {{ $dupeEntry['user']->alias ?? 'No alias' }}
                                                    <br>
                                                    <small class="text-muted">
                                                        Same {{ implode(' + ', $dupeEntry['reasons']) }}
                                                    </small>
                                                    @if($dupeEntry['user']->email)
                                                        <br><small class="text-muted">{{ $dupeEntry['user']->email }}</small>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users', ['userId' => $entry['user']->id]) }}" class="btn btn-sm btn-outline-secondary mb-1">
                                            Manage
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection
