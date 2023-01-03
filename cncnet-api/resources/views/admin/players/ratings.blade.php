@extends('layouts.app')
@section('title', 'Ladder')

@section('feature-image', '/images/feature/feature-index.jpg')

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>User Ratings</span>
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
                        User Ratings
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

                    <a href="/admin/players/ratings/{{ $abbreviation }}/update" class="btn mt-3 mb-3 btn-size-md btn-primary">
                        Update {{ $abbreviation }} User Ratings
                    </a>

                    @include('components.pagination.paginate', ['paginator' => $users->appends(request()->query())])

                    <p class="lead">
                        <?php if ($search) : ?>
                        Searching for {{ $search }}
                        <?php endif; ?>
                    </p>

                    <div class="search mb-2 mt-2">
                        <form action="/admin/players/ratings/{{ $abbreviation }}">
                            <input class="form-control" name="search" placeholder="Search by player username" value="{{ $search }}"
                                style="height: 50px" />
                            <a href="?" style="color: silver;padding: 5px;margin-bottom: 5px; display: block;float: right;">
                                Clear search
                            </a>
                        </form>
                    </div>

                    <p>
                        Warning: Changing a users elo rating will also move them into the appropriate tier for this month.
                    </p>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th>Current Tier</th>
                                    <th>Current Rating</th>
                                    <th>Change Elo
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td width="50%">
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
                                                            href="{{ \App\URLHelper::getPlayerProfileUrl($player->ladder->currentHistory(), $player->username) }}">
                                                            {{ $player->username }}
                                                        </a>
                                                    </span>
                                                </span>
                                            @endforeach

                                            @if (count($usernames) == 0)
                                                No {{ $history->ladder->name }} usernames for this user
                                            @endif
                                        </td>
                                        <td>
                                            {{ \App\Http\Services\UserRatingService::getTierByLadderRules($user->rating, $history) }}
                                        </td>
                                        <td>
                                            Rating: {{ $user->rating }} <br />
                                            Rated games:{{ $user->rated_games }}<br />
                                            Peek rating: {{ $user->peak_rating }} <br />
                                        </td>
                                        <td>
                                            <form method="POST" action="/admin/players/ratings/{{ $abbreviation }}/update-user-rating">
                                                {{ csrf_field() }}
                                                <input type="hidden" name="user_id" value="{{ $user->id }}" />
                                                <input type="number" name="new_rating" value="{{ $user->rating }}" class="form-control" />
                                                <button type="submit" class="btn btn-outline btn-size-md">Save User Rating</button>
                                            </form>
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
