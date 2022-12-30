@extends('layouts.app')
@section('title', 'Ladder Users')

@section('feature-image', '/images/feature/feature-index.jpg')
@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Admin</span>
                    </h1>
                </div>
            </div>
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
        </div>
    </div>
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
        <section class="light-texture game-detail supported-games">
            <div class="container">
                <div class="feature">

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
                                    <form>
                                        <input type="hidden" name="userId" value="{{ $userId }}" />
                                        <input type="hidden" name="hostname" value="{{ $hostname }}" />

                                        <input class="form-control" name="search" placeholder="Search by player username" value="{{ $search }}"
                                            style="height: 50px" />
                                        <a href="?" style="color: silver;padding: 5px;margin-bottom: 5px; display: block;float: right;">
                                            Clear search
                                        </a>
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

                                    <div class="user-info">
                                        <h4><a href="?userId={{ $user->id }}">{{ $user->name }}</a></h4>
                                        <h5>User id: <strong>{{ $user->id }}</strong></h5>
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
                                    <h5>Email: <strong>{{ $user->email }}</strong></h5>

                                    <div class="base-info">
                                        Created: {{ $user->created_at->toDateString() }}
                                    </div>

                                    <a href="users/edit/{{ $user->id }}" class="btn btn-primary">Edit user</a>

                                    @include('admin._duplicates', [$user, $hostname])
                                    @include('admin._bans', [$user])
                                    @include('admin._nicknames')
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
