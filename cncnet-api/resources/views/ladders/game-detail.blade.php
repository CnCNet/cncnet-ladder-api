@extends('layouts.app')

<?php

$pageTitle = 'Viewing Game - ';
$reports = $playerGameReports;

?>

@foreach ($reports as $k => $pgr)
    <?php
    $player = $pgr->player()->first();
    $clan = $pgr->clan()->first();
    if ($k == 1) {
        $pageTitle .= ' vs ';
    }
    $pageTitle .= "$player->username";
    ?>
@endforeach

@section('title', $pageTitle)
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev($history->ladder->abbreviation))
@section('page-body-class', $history->ladder->abbreviation)

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
                    <a href="{{ \App\Models\URLHelper::getLadderUrl($history) }}">
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

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="{{ \App\Models\URLHelper::getLadderLogoByAbbrev($history->ladder->abbreviation) }}" alt="{{ $history->ladder->name }}"
                        class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
                    </h1>

                    <p class="lead">
                        <?php $reports = $playerGameReports; ?>
                        @foreach ($reports as $k => $pgr)
                            <?php $gameStats = $pgr->stats; ?>
                            <?php $player = $pgr->player()->first(); ?>

                            @if ($history->ladder->clans_allowed)
                                @if ($player->clanPlayer)
                                    Clan <strong>{{ $player->clanPlayer->clan->short }}</strong>
                                @endif
                            @else
                                <span>{{ $player->username }}</span>
                            @endif
                            @if ($k == 0)
                                <span><strong>VS</strong></span>
                            @endif
                        @endforeach
                    </p>

                    <p class="text-uppercase">
                        @if ($history->ladder->clans_allowed)
                            {{ $history->starts->format('F Y') }} - <strong>Clan Ranked Match</strong>
                        @else
                            {{ $history->starts->format('F Y') }} - <strong>1 vs 1 Ranked Match</strong>
                        @endif
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
                            <a href="{{ \App\Models\URLHelper::getLadderUrl($history) }}">
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
    @if (\Auth::user() && \Auth::user()->isLadderMod($history->ladder))
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
            $map = \App\Models\Map::where('hash', '=', $game->hash)->first();
        @endphp

        <div class="game-players-container">
            <div class="container">
                <section class="game-players">
                    @foreach ($playerGameReports as $k => $pgr)
                        @php $gameStats = $pgr->stats; @endphp
                        @php $player = $pgr->player()->first(); @endphp
                        @php $playerCache = $player->playerCache($history->id);@endphp
                        @php $playerRank = $playerCache ? $playerCache->rank() : 0; @endphp
                        @php $points = $playerCache ? $playerCache->points : 0;@endphp

                        @if ($k == floor($history->ladder->qmLadderRules->player_count) / 2)
                            <div class="text-center mt-5 mb-5 mt-lg-0 mb-lg-0">
                                <div class="player-vs d-flex align-items-center">
                                    <h1>Vs</h1>
                                </div>
                                <div class="match-details text-center mt-2">
                                    <h6>{{ $game->qmMatch?->map?->description }}</h4>
                                        <p>
                                            {{ $gameReport->created_at->diffForHumans() }} -
                                            <em>{{ $gameReport->created_at->format('Y-m-d') }}</em>
                                            <br />
                                            <strong>Duration:</strong> {{ gmdate('H:i:s', $gameReport->duration) }}
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
                            </div>
                        @endif

                        @include('ladders.game._player-card')
                    @endforeach
                </section>
            </div>
        </div>

        <div class="mt-2">
            @include('ladders.game._map-preview-with-players', [
                'map' => $map,
                'playerGameReports' => $playerGameReports,
            ])
        </div>

        <section class="game {{ $gameAbbreviation }} mt-2 mb-2">
            <div class="container">
                @include('ladders.game._game-cameo-stats', [
                    'playerGameReports' => $playerGameReports,
                    'abbreviation' => $gameAbbreviation,
                ])
            </div>
        </section>

    </section>
@endsection
