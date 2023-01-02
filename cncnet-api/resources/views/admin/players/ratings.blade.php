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
                        <span>Player Ratings</span>
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
                        Player Ratings
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
                        Player Ratings
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
                        @foreach ($ladders as $ladder)
                            <div>
                                <a href="/admin/players/ratings/{{ $ladder->abbreviation }}"
                                    class="btn me-3 btn-size-md {{ $abbreviation == $ladder->abbreviation ? 'btn-primary' : 'btn-outline' }}">
                                    {{ $ladder->abbreviation }}
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <h4>Players in Tier 1: {{ $tier1Count }}</h4>
                    <h4>Players in Tier 2: {{ $tier2Count }}</h4>

                    @include('components.pagination.paginate', ['paginator' => $players->appends(request()->query())])


                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th>Current Tier</th>
                                    <th>Current Rating</th>
                                    <th>Player Game History</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($players as $player)
                                    @php
                                        $playerHistory = \App\PlayerHistory::where('player_id', $player->id)->get();
                                        $currentTier = \App\PlayerRating::where('player_id', $player->id)->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $player->username }}
                                        </td>
                                        <td>
                                            {{ \App\Http\Services\PlayerRatingService::getTierByLadderRules($player->rating, $history) }}
                                        </td>
                                        <td>
                                            Rating: {{ $player->rating }} <br />
                                            Rated games:{{ $player->rated_games }}<br />
                                            Peek rating: {{ $player->peak_rating }} <br />
                                        </td>
                                        <td>
                                            @foreach ($playerHistory as $ph)
                                                <div><strong>{{ $ph->ladderHistory->starts }}</strong> - Tier: {{ $ph->tier }} </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @include('components.pagination.paginate', ['paginator' => $players->appends(request()->query())])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
