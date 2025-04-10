@extends('layouts.app')
@section('title', 'Canceled Matches')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet Canceled Matches</x-slot>
        <x-slot name="description">
            View all canceled games
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
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="mt-4">
        <div class="container">
            @include('components.pagination.paginate', ['paginator' => $canceled_matches->appends(request()->query())])
            
            <table class="table col-md-12">
                <thead>
                    <tr>
                        <th>Created At</th>
                        <th>Canceled By</th>
                        <th>Affected Players</th>
                        <th>Map</th>
                    </tr>
                </thead>
                <tbody class="table">
                    @foreach ($canceled_matches as $canceled_match)
                        <tr title="QM Match ID: {{ $canceled_match->qm_match_id }}">
                            <td>{{ $canceled_match->created_at->format('F j, Y, g:i a T') }}</td>
                            <td>{{ $canceled_match->canceled_by }}</td>
                            <td>{{ $canceled_match->affected_players }}</td>
                            <td>{{ $canceled_match->map }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @include('components.pagination.paginate', ['paginator' => $canceled_matches->appends(request()->query())])
        </div>
    </section>
@endsection
