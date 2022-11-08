@extends('layouts.app')
@section('title', $ladderPlayer->username)
@section('body-class', 'body-player-detail')

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
@endsection

@section('cover')/images/feature/feature-{{ $history->ladder->abbreviation }}.jpg
@endsection

@section('content')
    <section class="player-detail">
        <div class="container">

            <section class="player-header">
                <div class="player-stats">
                    <div class="player-profile">
                        <div class="player-avatar">
                            @include('components.avatar', ['avatar' => $userPlayer->getUserAvatar(), 'size' => 150])
                        </div>
                        <div class="player-rank">
                            <h1 class="username">{{ $ladderPlayer->username }}</h1>
                            <h3 class="rank highlight text-uppercase mt-0">Rank #1</h3>
                        </div>
                        <div class="player-social">
                            @if ($userPlayer->getTwitchProfile())
                                <a href="{{ $userPlayer->getTwitchProfile() }}"><i class="fa-brands fa-twitch fa-lg"></i></a>
                            @endif
                            @if ($userPlayer->getYouTubeProfile())
                                <a href="{{ $userPlayer->getYouTubeProfile() }}"><i class="fa-brands fa-youtube fa-lg"></i></a>
                            @endif
                        </div>
                    </div>

                    <div class="player-overall-stats">
                        <div class="stat-column">
                            <h4 class="stat-title">Player stats</h4>
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

                        <div class="stat-column">
                            <h4 class="stat-title">Top factions played</h4>
                            <div>
                                @include('ladders.components.player-factions')
                            </div>
                        </div>

                        <div class="stat-column">
                            <h4 class="stat-title">Daily progress</h4>
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

    <section class="player-games">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    {!! $games->render() !!}
                </div>

                @include('components.player-recent-games', ['player' => $ladderPlayer, 'games' => $games])

                <div class="row">
                    <div class="col-md-12 text-center">
                        {!! $games->render() !!}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="player-maps">
        <div class="container">
            <div class="row">
                @include('ladders.components.player-map-stats')
            </div>
        </div>
    </section>
@endsection

@if ($history->ends > Carbon\Carbon::now())
    @include('components.countdown', ['target' => $history->ends->toISO8601String()])
@endif
