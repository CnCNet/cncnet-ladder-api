@extends('layouts.app')
@section('title', 'Ladder Users')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet Ladder Users</x-slot>
        <x-slot name="description">
            View and manage ladder users
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
    <div class="admin-users mt-5">
        <section>
            <div class="container">

                <div class="row">
                    <div class="col-md-12">
                        <div class="players">
                            <h2>Filter by player username</h2>
                            <p class="lead">
                                <?php if ($search) : ?>
                                Searching for {{ $search }}
                                <?php endif; ?>
                            </p>

                            <div class="search" style="margin-bottom: 15px; max-width: 500px">
                                <form method="GET">
                                    <input type="hidden" name="hostname" value="{{ $hostname }}" />

                                    <input class="form-control mb-2" name="search" placeholder="Search by player username" value="{{ $search }}" style="height: 50px" />

                                    <input class="form-control mb-2" name="userIdOrAlias" placeholder="Search by user ID or alias" value="{{ request('userIdOrAlias') }}" style="height: 50px" />

                                    <div class="d-flex justify-content-between align-items-center">
                                        <button type="submit" class="btn btn-primary">Filter</button>

                                        <a href="{{ url()->current() }}" style="color: silver; padding: 5px; margin-bottom: 5px; text-align:right;">
                                            Clear search
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <div class="users">
                                <?php $unique = []; ?>
                                <?php foreach ($players as $pResult) : ?>

                                <?php
                                $user = $pResult->user()->first();
                                if ($user == null) {
                                    continue;
                                }
                                
                                if (in_array($user->id, $unique)) {
                                    continue;
                                }
                                $unique[] = $user->id;
                                ?>

                                <style>
                                    .user-info {
                                        margin-bottom: 50px;
                                        background: #1e212d;
                                        border-radius: 4px;
                                        padding: 1rem;
                                    }
                                </style>

                                <div class="user-info">
                                    <h4><a href="?userId={{ $user->id }}">{{ $user->name }}</a></h4>
                                    <h5>User id: <strong>{{ $user->id }}</strong></h5>
                                    @if ($user->alias != null)
                                        <h5>Alias: <strong>{{ $user->alias }}</strong></h5>
                                    @endif
                                    <h5>Email: <strong>{{ $user->email }}</strong></h5>
                                    <div class="base-info">
                                        Created: {{ $user->created_at->toDateString() }}
                                    </div>

                                    @include('admin._duplicates', [$user])
                                    @include('admin._bans', [$user])
                                    @include('admin._nicknames')
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if ($users) : ?>
                        <h2>Users</h2>
                        <?php endif; ?>
                        <div class="users">
                            <?php foreach ($users as $user) : ?>
                            <div class="user-info">
                                <h4><a href="?userId={{ $user->id }}">{{ $user->name }}</a></h4>
                                <h5>User id: <strong>{{ $user->id }}</strong></h5>
                                @if ($user->alias != null)
                                    <h5>Alias: <strong>{{ $user->alias }}</strong></h5>
                                @endif
                                <h5>Email: <strong>{{ $user->email }}</strong></h5>

                                <div class="base-info">
                                    Created: {{ $user->created_at->toDateString() }}
                                </div>

                                <a href="users/edit/{{ $user->id }}" class="btn btn-primary btn-size-md mt-2 mb-2">Edit user</a>

                                @include('admin._duplicates', [$user, $hostname])
                                @include('admin._bans', [$user])
                                @include('admin._nicknames')
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>
@endsection
