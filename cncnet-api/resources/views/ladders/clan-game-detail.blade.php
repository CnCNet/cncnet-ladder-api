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
    <x-hero-split>
        <x-slot name="subpage">true</x-slot>
        <x-slot name="video">{{ \App\Models\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation) }}</x-slot>
        <x-slot name="title">
            <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
        </x-slot>

        <x-slot name="description">
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
        </x-slot>

        <x-slot name="logo">
            <img src="{{ \App\Models\URLHelper::getLadderLogoByAbbrev($history->ladder->abbreviation) }}" alt="{{ $history->ladder->name }}" />
        </x-slot>


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
    </x-hero-split>
@endsection

@section('content')

    @if (\Auth::user() && \Auth::user()->isLadderMod($history->ladder))
        <div class="container mb-5 mt-5">
            <a class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#adminTools" aria-expanded="false" aria-controls="adminTools">
                Show admin tools
            </a>
            <div class="collapse " id="adminTools">
                @include('ladders.game._admin-game-tools')
            </div>
        </div>
    @endif

    <section class="game-detail clan-detail">
        @php
            $gameAbbreviation = $history->ladder()->first()->abbreviation;
            $map = \App\Models\Map::where('hash', '=', $game->hash)->first();
        @endphp

        <div class="clan-versus-header">
            <div class="container">
                <div class="clan-versus-header-container">

                    @foreach ($clanGameReports as $k => $cgr)
                        @if ($cgr)
                            @php $clanPointReport = $cgr->gameReport->getPointReportByClan($cgr->clan_id); @endphp

                            @if ($clanPointReport && $cgr->clan)
                                @php $url = \App\Models\URLHelper::getClanProfileUrl($history, $cgr->clan->short); @endphp
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

        <div class="match-details text-center mt-2">
            <h4>{{ $game->scen }}</h4>
            <p>
                <strong>Date played:</strong> {{ $gameReport->created_at->diffForHumans() }} -
                <em>{{ $gameReport->created_at->format('Y-m-d') }}</em>
                <br />
                <strong>Game duration:</strong> {{ gmdate('H:i:s', $gameReport->duration) }}
                <br />
                <strong>FPS:</strong> {{ $gameReport->fps }}
                <br />
            </p>

            <div style="font-size:0.8rem" class="mb-1">
                <strong>Tunnel(s):</strong>
                <br />
                @foreach ($tunnels as $tunnel)
                    * {{ $tunnel }}
                    <br />
                @endforeach
            </div>
            @if ($gameReport !== null)
                @if ($gameReport->oos)
                    <br />
                    <strong>Game ended in reconnection error (OOS)</strong>
                @endif
                @if ($gameReport->disconnected())
                    <strong>Game disconnected</strong>
                @endif
            @endif
        </div>

        @include('ladders.game._map-preview-with-players', [
            'map' => $map,
            'playerGameReports' => $playerGameReports,
        ])

        <section class="game {{ $gameAbbreviation }} mt-2 mb-2">
            <div class="container max-container">
                @include('ladders.game._game-cameo-stats', [
                    'playerGameReports' => $orderedClanReports,
                    'abbreviation' => $gameAbbreviation,
                ])
            </div>
        </section>
    </section>
@endsection
