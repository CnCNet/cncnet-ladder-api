@extends('layouts.app')
@php $pageTitle = "Viewing Game - ";@endphp
@foreach ($playerGameReports as $k => $pgr)
    @php $player = $pgr->player()->first(); @endphp
    @php
        if ($k == 1) {
            $pageTitle .= ' vs ';
        }
    @endphp
    @php $pageTitle .= "$player->username"; @endphp
@endforeach

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
                    <a href="{{ \App\URLHelper::getLadderUrl($history) }}">
                        <span class="material-symbols-outlined icon pe-3">
                            military_tech
                        </span>
                        {{ $history->ladder->name }}
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <a href="">
                        <span class="material-symbols-outlined pe-3">
                            swords
                        </span>
                        {{ $pageTitle }}
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('title', $pageTitle)
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev($history->ladder->abbreviation))

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="/images/games/{{ $history->ladder->abbreviation }}/logo.png" alt="{{ $history->ladder->name }}" class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
                    </h1>

                    <p class="lead text-uppercase">
                        @foreach ($playerGameReports as $k => $pgr)
                            <?php $gameStats = $pgr->stats; ?>
                            <?php $player = $pgr->player()->first(); ?>

                            <span>{{ $player->username }}</span>
                            @if ($k == 0)
                                <span><strong>VS</strong></span>
                            @endif
                        @endforeach
                    </p>
                    <p class="text-uppercase">
                        {{ $history->starts->format('F Y') }} - <strong>1 vs 1 Ranked Match</strong>
                    </p>

                    <div class="mini-breadcrumb d-none d-lg-flex">
                        <div class="mini-breadcrumb-item">
                            <a href="/" class="">
                                <span class="material-symbols-outlined">
                                    home
                                </span>
                            </a>
                        </div>
                        <div class="mini-breadcrumb-item">
                            <a href="{{ \App\URLHelper::getLadderUrl($history) }}">
                                <span class="material-symbols-outlined icon">
                                    military_tech
                                </span>
                                {{ $history->ladder->name }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (\Auth::user() && \Auth::user()->isLadderMod($history))
        <div class="container mt-5 mb-5">
            <a class="btn btn-outline" data-bs-toggle="collapse" data-bs-target="#adminTools" aria-expanded="false" aria-controls="adminTools">
                Show admin tools
            </a>
            <div class="collapse " id="adminTools">
                @include('ladders.game._admin-game-tools')
            </div>
        </div>
    @endif

    <section class="game-detail">
        @php
            $gameAbbreviation = $history->ladder()->first()->abbreviation;
            $map = \App\Map::where('hash', '=', $game->hash)->first();
        @endphp

        <div class="game-players-container">
            <div class="container">
                <section class="game-players">
                    @foreach ($playerGameReports as $k => $pgr)
                        @php $gameStats = $pgr->stats; @endphp
                        @php $player = $pgr->player()->first(); @endphp
                        @php $playerCache = $player->playerCache($history->id);@endphp
                        @php $rank = $playerCache ? $playerCache->rank() : 0; @endphp
                        @php $points = $playerCache ? $playerCache->points : 0;@endphp

                        <div class="player-card {{ $k == 1 ? 'player-card-right' : '' }}">
                            <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
                                <div class="player-avatar">
                                    @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 150])
                                </div>
                            </a>

                            <div class="player-details">
                                <h2 class="username">
                                    <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
                                        {{ $player->username }}
                                    </a>
                                </h2>

                                <h5 class="rank pb-1">
                                    Rank #{{ $rank }}
                                </h5>

                                <div class="d-flex">
                                    <div class="faction">
                                        @if ($pgr->stats)
                                            @php $playerStats2 = \App\Stats2::where("id", $pgr->stats->id)->first(); @endphp
                                            @php $playerCountry = $playerStats2->faction($history->ladder->game, $pgr->stats->cty); @endphp
                                            <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
                                        @endif
                                    </div>
                                    <div class="points {{ $pgr->won ? 'won' : 'lost' }}">
                                        @if ($pgr->points >= 0)
                                            <span>{{ '+' }}</span>
                                        @endif
                                        {{ $pgr->points }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($k == 0)
                            <div class="player-vs d-flex align-items-center">
                                <h1>Vs</h1>
                            </div>
                        @endif
                    @endforeach
                </section>
            </div>
        </div>

        <div class="match-details text-center mt-5">
            <h4>{{ $game->scen }}</h4>
            <p>
                <strong>Date played:</strong> {{ $gameReport->created_at->diffForHumans() }} - <em>{{ $gameReport->created_at->format('Y-m-d') }}</em>
                <br />
                <strong>Game duration:</strong> {{ gmdate('H:i:s', $gameReport->duration) }}
                <br />
                <strong>FPS:</strong> {{ $gameReport->fps }}

                @if ($gameReport !== null)
                    @if ($gameReport->oos)
                        <br />
                        <strong>Game ended in reconnection error (OOS)</strong>
                    @endif
                    @if ($gameReport->disconnected())
                        <strong>Game disconnected</strong>
                    @endif
                @endif
            </p>
        </div>

        @include('ladders.game._map-with-players', [
            'map' => $map,
        ])

        @include('ladders.game._game-cameo-stats', [
            'playerGameReports' => $playerGameReports,
            'abbreviation' => $gameAbbreviation,
        ])
    </section>
@endsection

@if ($history->ends > Carbon\Carbon::now())
    @include('components.countdown', ['target' => $history->ends->toISO8601String()])
@endif
