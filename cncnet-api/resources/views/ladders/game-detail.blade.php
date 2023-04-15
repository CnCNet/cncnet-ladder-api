@extends('layouts.app')

<?php

$pageTitle = 'Viewing Game - ';
$reports = $isClanGame ? $clanGameReports : $playerGameReports;

?>

@foreach ($reports as $k => $pgr)
    <?php
    $player = $pgr->player()->first();
    $clan = $pgr->clan()->first();
    if ($k == 1) {
        $pageTitle .= ' vs ';
    }
    if ($isClanGame) {
        $pageTitle .= "$clan->short";
    } else {
        $pageTitle .= "$player->username";
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
                    <img src="{{ \App\URLHelper::getLadderLogoByAbbrev($history->ladder->abbreviation) }}" alt="{{ $history->ladder->name }}"
                        class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
                    </h1>

                    <p class="lead">
                        <?php $reports = $isClanGame ? $clanGameReports : $playerGameReports; ?>
                        @foreach ($reports as $k => $pgr)
                            <?php $gameStats = $pgr->stats; ?>
                            <?php $player = $pgr->player()->first(); ?>

                            @if ($history->ladder->clans_allowed)
                                Clan <strong>{{ $player->clanPlayer->clan->short }}</strong>
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
                            <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}"
                                title="View {{ $player->username }}'s profile">
                                <div class="player-avatar">
                                    @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 50])
                                </div>
                            </a>

                            <div class="player-details">
                                <h2 class="username">
                                    @if ($isClanGame)
                                        <a href="{{ \App\URLHelper::getClanProfileLadderUrl($history, $player->clanPlayer->clan->id) }}"
                                            title="View {{ $player->clanPlayer->clan->short }}'s clan profile">
                                            {{ $player->clanPlayer->clan->short }}

                                            <br />
                                            <span style="font-size: 1rem">Played by: {{ $player->username }}</span>
                                        </a>
                                    @else
                                        <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}"
                                            title="View {{ $player->username }}'s profile">
                                            {{ $player->username }}
                                        </a>
                                    @endif
                                </h2>

                                <h5 class="rank pb-1">
                                    Rank #{{ $rank }}
                                    <br />

                                    <div style="font-size: 0.8rem" class="mt-2 mb-2">
                                        <?php $tier = $player->getCachedPlayerTierByLadderHistory($history); ?>
                                        {!! \App\Helpers\LeagueHelper::getLeagueIconByTier($tier) !!}
                                        - {{ \App\Helpers\LeagueHelper::getLeagueNameByTier($tier) }}
                                    </div>
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

        @include('ladders.game._map-preview-with-players', [
            'map' => $map,
            'playerGameReports' => $playerGameReports,
        ])

        @include('ladders.game._game-cameo-stats', [
            'playerGameReports' => $playerGameReports,
            'abbreviation' => $gameAbbreviation,
        ])
    </section>
@endsection
