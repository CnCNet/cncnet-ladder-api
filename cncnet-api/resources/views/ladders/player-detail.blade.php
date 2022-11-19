@extends('layouts.app')
@section('title', $ladderPlayer->username)
@section('body-class', 'body-player-detail')

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
@endsection

@section('body-feature-image', '/images/feature/feature-' . $history->ladder->abbreviation . '.jpg')

@section('content')
    <div class="player-detail">
        <section>
            <section class="pb-4">
                <div class="container">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/">
                                    <span class="material-symbols-outlined">
                                        home
                                    </span>
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ \App\URLHelper::getLadderUrl($history) }}">
                                    <span class="material-symbols-outlined icon pe-3">
                                        military_tech
                                    </span>
                                    Ladders
                                </a>
                            </li>
                            <li class="breadcrumb-item active">
                                <a href="">
                                    <span class="material-symbols-outlined icon pe-3">
                                        person
                                    </span>
                                    Viewing {{ $ladderPlayer->username }}
                                </a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </section>

            <div class="container">

                <section class="player-header">
                    <div class="player-profile">
                        <div class="player-avatar me-5">
                            @include('components.avatar', ['avatar' => $userPlayer->getUserAvatar(), 'size' => 150])
                        </div>
                        <div class="player-rank pt-3 me-5">
                            <h1 class="username">{{ $ladderPlayer->username }}</h1>
                            <h3 class="rank highlight text-uppercase mt-0">Rank #{{ $ladderPlayer->rank }}</h3>
                        </div>
                        <div class="player-social pt-4 me-5">
                            @if ($userPlayer->getTwitchProfile())
                                <a href="{{ $userPlayer->getTwitchProfile() }}">
                                    <i class="bi bi-twitch"></i>
                                </a>
                            @endif
                            @if ($userPlayer->getYouTubeProfile())
                                <a href="{{ $userPlayer->getYouTubeProfile() }}">
                                    <i class="bi bi-youtube"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="player-stats">
                        <div class="player-overall-stats grid">
                            <div class="column">
                                <h5 class="stat-title">Player stats</h5>
                                <div class="player-stats-drilldown stats-wrap">
                                    <div class="stat-item">
                                        <span class="name">Points:</span> {{ $ladderPlayer->points }}
                                    </div>
                                    <div class="stat-item">
                                        <span class="name">Games:</span>{{ $ladderPlayer->game_count }}
                                    </div>
                                    <div class="stat-item">
                                        <span class="name">Wins:</span> {{ $ladderPlayer->games_won }}
                                    </div>
                                    <div class="stat-item">
                                        <span class="name">Losses:</span> {{ $ladderPlayer->games_won }}
                                    </div>
                                    <div class="stat-item">
                                        <span class="name">Average FPS:</span> {{ $ladderPlayer->average_fps }}
                                    </div>
                                    <div class="stat-item">
                                        <span class="name">Played today:</span> {{ $playerGamesLast24Hours }}
                                    </div>
                                </div>
                            </div>

                            <div class="column">
                                <h5 class="stat-title">Top factions played</h5>
                                <div>
                                    @include('ladders.components.player-factions')
                                </div>
                            </div>

                            <div class="column">
                                <h5 class="stat-title">Played this month</h5>
                                <div>
                                    @include('ladders.components.player-chart')
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        @if ($playerOfTheDayAward)
                            @include('ladders.components.award-player-of-the-day', [
                                'wins' => $playerOfTheDayAward->wins,
                                'username' => $playerOfTheDayAward->username,
                            ])
                        @endif
                    </div>
                    {{-- <div class="player-achievements">
                    <h3>Player achievements</h3>
                </div> --}}
                </section>
            </div>
        </section>

        <section class="mt-5">
            <div class="container">
                <div class="row">
                    @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
                    @include('components.player-recent-games', ['player' => $ladderPlayer, 'games' => $games])
                </div>
                <div class="row pt-3 pb-3">
                    @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
                </div>
            </div>
        </section>

        <section class="player-maps pt-5 pb-5">
            <div class="container">
                <div class="row">
                    @include('ladders.components.player-map-stats')
                </div>
            </div>
        </section>
    </div>
@endsection

@if ($history->ends > Carbon\Carbon::now())
    @include('components.countdown', ['target' => $history->ends->toISO8601String()])
@endif
