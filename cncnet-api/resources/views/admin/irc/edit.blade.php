@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet IRC Management</x-slot>
        <x-slot name="description">
            CnCNet IRC Management
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

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <div class="row mb-5">
                <div class="col-12 text-end">
                    <a href="{{ route('admin.irc') }}" class="btn">Back</a>
                    <a href="{{ route('admin.irc.bans') }}" class="btn btn-secondary">View all bans</a>
                </div>
            </div>

            <div class="row">
                <div class="col-12">

                    <h3>Ban Details</h3>

                    @if ($ban->expires_at != null)
                        <p>This ban was originally set to expire: {{ $ban->ban_original_expiry ?? 'Never' }}</p>
                        <p>
                            @if ($ban->ban_original_expiry)
                                Original Ban Length:
                                {{ round($ban->created_at->diffInDays($ban->ban_original_expiry)) }} days
                                <br />
                            @endif
                            Updated Ban Length: {{ round($ban->created_at->diffInDays($ban->expires_at)) }} day
                        </p>

                        @if ($ban->expires_at?->isPast())
                            <div class="badge rounded-pill text-bg-info fs-6 mb-4">Note: This ban has now expired</div> <br />
                        @endif
                    @else
                        <p>This ban is permanent and not set to expire.</p>
                    @endif

                    <div class="mb-5">
                        @include('admin.irc.components.ban-expire-form', ['ban' => $ban])
                    </div>

                    <h3>Edit ban</h3>
                    @include('admin.irc.components.ban-form', ['ban' => $ban])

                    <div class="mt-5">
                        <h4>Ban Change History</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Admin</th>
                                        <th>When</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ban->logs as $log)
                                        <tr>
                                            <td>{{ $log->action }}</td>
                                            <td>{{ \App\Models\User::find($log->admin_id)->name }}</td>
                                            <td>{{ $log->created_at->format('F j, Y, g:i a T') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
