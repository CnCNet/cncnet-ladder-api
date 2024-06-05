@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet Admin</x-slot>
        <x-slot name="description">
            Top Secret Clearance Required
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
    <section class="pt-4 mt-5 mb-5">
        <div class="container">

            <div style="max-width: 400px">

                <h2 class="mb-0">
                    <span class="material-symbols-outlined">social_leaderboard</span>
                    Podium
                </h2>
                <div class="small text-muted mb-4">
                    Period
                    {{ $from->format('Y-m-d H:i') }} to {{ $to->format('Y-m-d H:i') }} UTC
                </div>

                @if($players->count() > 0)

                    <p>Top 3 players - {{ $ladder->name }}</p>

                    <ul>
                        @foreach($players as $i => $player)
                            <li>
                                @if($i === 0)
                                    🥇
                                @elseif($i === 1)
                                    🥈
                                @elseif($i === 2)
                                    🥉
                                @endif
                                {{ $player->username }}
                                <small>
                                    with <strong>{{ $player->win_count }} wins</strong>
                                </small>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>No data for the selected period.</p>
                @endif

            </div>

            <a href="{{ route('admin.podium') }}" class="btn btn-primary" style="margin-top: 100px;">
                Back to form
            </a>

        </div>
    </section>
@endsection
