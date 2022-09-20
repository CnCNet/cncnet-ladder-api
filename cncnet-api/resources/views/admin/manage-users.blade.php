@extends('layouts.app')
@section('title', 'Ladder Users')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet Admin
                </h1>
                <p>Admin panel for managing Users</p>

                <a href="/admin/" class="btn btn-transparent btn-lg">
                    <i class="fa fa-chevron-left fa-lg fa-fw" aria-hidden="true"></i> Back to Admin
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="admin-users">
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
                                    <input type="hidden" name="hostname" value="{{ $hostname}}" />

                                    <input class="form-control" name="search" placeholder="Search by player username" value="{{ $search }}" style="height: 50px" />
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
                                    if ($user == null)
                                    {
                                        continue;
                                    }

                                    if (in_array($user->id, $unique))
                                    {
                                        continue;
                                    }
                                    $unique[] = $user->id;
                                    ?>

                                    <div class="user-info">
                                        <h4><a href="?userId={{$user->id}}">{{ $user->name }}</a></h4>
                                        <h5>User id: <strong>{{ $user->id }}</strong></h5>

                                        <div class="base-info">
                                            Created: {{ $user->created_at->toDateString()}}
                                        </div>

                                        @include("admin._duplicates", [$user])
                                        @include("admin._bans", [$user])
                                        @include("admin._nicknames")
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
                                    <h4><a href="?userId={{$user->id}}">{{ $user->name }}</a></h4>
                                    <h5>User id: <strong>{{ $user->id }}</strong></h5>

                                    <div class="base-info">
                                        Created: {{ $user->created_at->toDateString()}}
                                    </div>

                                    @include("admin._duplicates", [$user, $hostname])
                                    @include("admin._bans", [$user])
                                    @include("admin._nicknames")
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