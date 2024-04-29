@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Player Ratings</x-slot>

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
                    <a href="">
                        <span class="material-symbols-outlined pe-3">
                            admin_panel_settings
                        </span>
                        User Ratings
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h3>Players Ratings - {{ $history->ladder->name }}</h3>

                    <div class="btn-group mb-5 mt-5">
                        @foreach ($ladders as $l)
                            <div>
                                <a href="/admin/players/ratings/{{ $l->abbreviation }}"
                                    class="btn me-3 btn-size-md {{ $abbreviation == $l->abbreviation ? 'btn-primary' : 'btn-outline' }}">
                                    {{ $l->abbreviation }}
                                </a>
                            </div>
                        @endforeach
                    </div>

                    @include('components.pagination.paginate', ['paginator' => $users->appends(request()->query())])

                    <p class="lead">
                        <?php if ($search) : ?>
                        Searching for {{ $search }}
                        <?php endif; ?>
                    </p>

                    @include('components.form-messages')

                    <div class="search mb-2 mt-2">
                        <form action="/admin/players/ratings/{{ $abbreviation }}">
                            <input class="form-control" name="search" placeholder="Search by player username" value="{{ $search }}"
                                style="height: 50px" />
                            <a href="?" style="color: silver;padding: 5px;margin-bottom: 5px; display: block;float: right;">
                                Clear search
                            </a>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Usernames</th>
                                    <th>
                                        User Tier
                                    </th>
                                    <th>Current Elo Ratings</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>
                                            {{ $user->name }}
                                            @if ($user->alias)
                                                <br />
                                                Alias: {{ $user->alias }}
                                            @endif
                                        </td>
                                        <td width="30%">
                                            @php
                                                $usernames = $user
                                                    ->usernames()
                                                    ->where('ladder_id', $history->ladder->id)
                                                    ->get();
                                            @endphp
                                            @foreach ($usernames as $player)
                                                <span class="d-flex">
                                                    <span class="me-1 icon-game icon-{{ $player->ladder->abbreviation }}"></span>
                                                    <span class="me-3">
                                                        <a
                                                            href="{{ \App\Models\URLHelper::getPlayerProfileUrl($player->ladder->currentHistory(), $player->username) }}">
                                                            {{ $player->username }}
                                                        </a>
                                                    </span>
                                                    <br />
                                                </span>
                                            @endforeach

                                            @if (count($usernames) == 0)
                                                No {{ $history->ladder->name }} usernames for this user
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="/admin/users/tier/update">
                                                {{ csrf_field() }}
                                                <input type="hidden" name="user_id" value="{{ $user->id }}" />
                                                <input type="hidden" name="ladder_id" value="{{ $history->ladder->id }}" />

                                                @php
                                                    $userTier = $user->getUserLadderTier($history->ladder);
                                                @endphp
                                                <input type="text" name="tier" class="form-control" value="{{ $userTier->tier }}" />

                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="canPlayBothTiers"
                                                        id="canPlayBothTiers_{{ $user->id }}" {{ $userTier->both_tiers ? 'checked' : '' }} />
                                                    <label class="form-check-label" for="canPlayBothTiers_{{ $user->id }}">
                                                        Can play both Tiers
                                                    </label>
                                                </div>

                                                <button type="submit" class="btn btn-outline btn-size-md">Save</button>
                                            </form>
                                        </td>

                                        <td>
                                            Rating: {{ $user->rating }} <br />
                                            Rated games:{{ $user->rated_games }}<br />
                                            Peek rating: {{ $user->peak_rating }} <br />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @include('components.pagination.paginate', ['paginator' => $users->appends(request()->query())])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
