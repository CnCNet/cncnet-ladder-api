@extends('layouts.app')

<?php

$pageTitle = 'Viewing Game - ';

?>

@foreach ($clanGameReports as $k => $pgr)
    <?php
    $player = $pgr->player()->first();
    $clan = $pgr->clan()->first();
    if ($k == 1) {
        $pageTitle .= ' vs ';
    }
    if ($clan) {
        $pageTitle .= "$clan->short";
    }
    ?>
@endforeach

@section('title', $pageTitle)
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev($history->ladder->abbreviation))

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
                        <i class="bi bi-flag-fill pe-3"></i>
                        {{ $history->ladder->name }}
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <a href="">
                        <i class="bi bi-flag-fill pe-3"></i>
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
                    <img src="{{ \App\URLHelper::getLadderLogoByAbbrev($history->ladder->abbreviation) }}" alt="{{ $history->ladder->name }}"
                        class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
                    </h1>

                    <p class="lead">
                        @foreach ($clanGameReports as $k => $pgr)
                            <?php $gameStats = $pgr->stats; ?>
                            <?php $player = $pgr->player()->first(); ?>

                            @if ($player->clanPlayer)
                                Clan <strong>{{ $player->clanPlayer->clan->short }}</strong>
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

    @if (\Auth::user() && \Auth::user()->isLadderMod($history->ladder))
        <div class="container mb-5">
            <a class="btn btn-outline" data-bs-toggle="collapse" data-bs-target="#adminTools" aria-expanded="false" aria-controls="adminTools">
                Show admin tools
            </a>
            <div class="collapse " id="adminTools">
                @include('ladders.game._admin-game-tools')
            </div>
        </div>
    @endif
@endsection

@section('content')
    <section class="game-detail">
        @php
            $gameAbbreviation = $history->ladder()->first()->abbreviation;
            $map = \App\Map::where('hash', '=', $game->hash)->first();
        @endphp

        <div class="clan-versus-header">
            <div class="container">
                <div class="clan-versus-header-container">

                    @foreach ($clanGameReports as $k => $cgr)
                        @if ($cgr)
                            @php $clanPointReport = $cgr->gameReport->getPointReportByClan($cgr->clan_id); @endphp

                            @if ($clanPointReport)
                                @php $url = \App\URLHelper::getClanProfileUrl($history, $cgr->clan->short); @endphp
                                @include('ladders.game.clan._clan-versus-header', [
                                    'clanName' => $cgr->clan->short,
                                    'clanProfileUrl' => $url,
                                    'avatar' => $cgr->clan->getClanAvatar(),
                                    'playerGameReport' => $clanPointReport,
                                    'order' => $k,
                                ])
                            @endif
                        @endif

                        @if ($k == 0)
                            <div class="clan-vs">
                                Vs
                            </div>
                        @endif
                    @endforeach
                </div>
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

                @foreach ($qmConnectionStats as $qmStat)
                    <?php $ipHost = $qmStat->ipAddress->address . ':' . $qmStat->port; ?>
                    {{ \App\Helpers\TunnelHelper::getTunnelNameByIpHost($ipHost) }}
                @endforeach

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

        @include('ladders.game._map-preview-with-players', [
            'map' => $map,
            'playerGameReports' => $playerGameReports,
        ])

        @include('ladders.game._game-cameo-stats', [
            'playerGameReports' => $orderedClanReports,
            'abbreviation' => $gameAbbreviation,
        ])
    </section>
@endsection
