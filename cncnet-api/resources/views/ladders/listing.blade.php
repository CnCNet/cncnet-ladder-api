@extends('layouts.app')
@section('title', $history->ladder->name . ' Ladder')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation))

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
                        <span>Ladder Rankings</span>
                    </h1>

                    <p class="lead text-uppercase">
                        <small>{{ $history->starts->format('F Y') }} - <strong>1 vs 1 Ranked Match</strong></small>
                    </p>
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
                <li class="breadcrumb-item active">
                    <a href="">
                        <span class="material-symbols-outlined icon pe-3">
                            military_tech
                        </span>
                        Ladders
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="ladder-listing game-{{ $history->ladder->abbreviation }}">
        <div class="container">

            @if ($history->ladder->abbreviation == 'blitz')
                <div class="qm-stats mt-4">
                    <a class="stat blue" href="https://youtu.be/n_xWvNxO55c" target="_blank" style="z-index:1;" title="How-To Setup & Play Blitz Online">
                        <div class="text-center">
                            <i class="fa fa-youtube fa-fw"></i>
                            <h4>How-To Setup & Play Blitz Online</h4>
                        </div>
                        <div class="text-center">
                            <div class="value">Watch on YouTube </div>
                        </div>
                    </a>

                    <a class="stat blue" href="https://youtu.be/EPDCaucx5qA" target="_blank" style="z-index:1;" title="Tips & Tricks for New Blitz Players">
                        <div class="text-center">
                            <i class="fa fa-youtube fa-fw fa-2x"></i>
                            <h4>Tips & Tricks for New Blitz Players</h4>
                        </div>
                        <div class="text-center">
                            <div class="value">Watch on YouTube </div>
                        </div>
                    </a>
                </div>
            @endif

            <section class="mt-4 ladder-info">
                <div>
                    <button type="button" class="btn btn-secondary d-flex" data-bs-toggle="modal" data-bs-target="#openLadderRules">
                        <span class="material-symbols-outlined pe-3">
                            gavel
                        </span>
                        Ladder Rules
                    </button>
                </div>

                @if ($history->ladder->qmLadderRules->ladder_discord != null)
                    <div>
                        <a href="{{ $history->ladder->qmLadderRules->ladder_discord }}" class="btn btn-secondary">
                            <i class="bi bi-discord pe-2"></i> {{ $history->ladder->name }} Discord
                        </a>
                    </div>
                @endif

                <div class="dropdown d-flex">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="material-symbols-outlined icon">
                            military_tech
                        </span>
                    </button>
                    <ul class="dropdown-menu">
                        @foreach ($ladders_previous as $previous)
                            <li>
                                <a href="/ladder/{{ $previous->short . '/' . $previous->ladder->abbreviation }}/" title="{{ $previous->ladder->name }}" class="dropdown-item">
                                    {{ $previous->short }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="mt-5 mb-5">
                @include('ladders.components.qm-stats', [
                    'stats' => $stats,
                    'history' => $history,
                    'statsPlayerOfTheDay' => $statsPlayerOfTheDay,
                ])
            </section>

            @include('ladders.components.past-ladders')

            @if (!$search)
                <section class="mt-5 mb-5">
                    <div class="row">
                        <div class="header">
                            <div class="col-md-12">
                                <h5>
                                    <strong>1vs1</strong>
                                    Recent Games
                                    <small>
                                        <a href="{{ '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/games' }}">View All Games</a>
                                    </small>
                                </h5>
                            </div>
                        </div>
                    </div>
                    @include('components.global-recent-games', ['games' => $games])
                </section>
            @endif

            <section>
                <div class="row">

                    <div class="col-md-12">
                        <div class="header">
                            <div class="row">
                                <div class="col-md-3">
                                    @if ($history->ladder->qmLadderRules->tier2_rating > 0)
                                        @if ($tier == 1 || $tier === null)
                                            <h3><strong>1vs1</strong> Masters League Rankings</h3>
                                        @elseif($tier == 2)
                                            <h3><strong>1vs1</strong> Contenders League Rankings</h3>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($history->ladder->qmLadderRules->tier2_rating > 0)
                            <div class="feature" style="margin-top: -25px;">
                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-6" style="margin-bottom:20px">
                                        <a href="/ladder/{{ $history->short . '/1/' . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="ladder-link">
                                            <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}"
                                                style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover-masters.png' }}')">
                                                <div class="details tier-league-cards">
                                                    <div class="type">
                                                        <h1>Masters <strong>League</strong></h1>
                                                        <p class="lead">1<strong>vs</strong>1</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-6" style="margin-bottom:20px">
                                        <a href="/ladder/{{ $history->short . '/2/' . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="ladder-link">
                                            <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}"
                                                style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover-contenders.png' }}')">
                                                <div class="details tier-league-cards">
                                                    <div class="type">
                                                        <h1>Contenders <strong>League</strong></h1>
                                                        <p class="lead">1<strong>vs</strong>1</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (request()->input('filterBy') == 'games')
                            <p>
                                You are ordering by game count, <a href="?#listing">reset by rank?</a>
                            </p>
                        @endif

                        <div class="d-flex mb-2">
                            @include('components.pagination.paginate', ['paginator' => $players->appends(request()->query())])
                            <div class="ms-auto">
                                <form>
                                    <div class="form-group" method="GET">
                                        <div class="search" style="position:relative;">
                                            <input class="form-control border" name="search" value="{{ $search }}" placeholder="Search by Player..." />
                                        </div>
                                        @if ($search)
                                            <small>
                                                Searching for <strong>{{ $search }}</strong> returned {{ count($players) }} results
                                                <a href="?search=">Clear?</a>
                                            </small>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="ladder-player-listing" id="listing">
                            <div class="player-row-header">
                                <div class="player-rank">
                                    Rank
                                </div>
                                <div class="player-avatar">
                                    Name
                                </div>
                                <div class="player-social">
                                    Social
                                </div>
                                <div class="player-points">Points</div>
                                <div class="player-wins">Won</div>
                                <div class="player-losses">Lost</div>

                                @if (request()->input('orderBy') == 'desc')
                                    <a class="player-games filter-link d-flex text-decoration-none" href="?filterBy=games&orderBy=asc#listing">
                                        Games
                                        <span class="material-symbols-outlined ms-1">
                                            expand_less
                                        </span>
                                    </a>
                                @else
                                    <a class="player-games filter-link d-flex text-decoration-none" href="?filterBy=games&orderBy=desc#listing">
                                        Games
                                        <span class="material-symbols-outlined ms-1">
                                            expand_more
                                        </span>
                                    </a>
                                @endif
                            </div>

                            @foreach ($players as $k => $playerCache)
                                @include('components/player-row', [
                                    'username' => $playerCache->player_name,
                                    'points' => $playerCache->points,
                                    'rank' => $playerCache->rank(),
                                    'wins' => $playerCache->wins,
                                    'losses' => $playerCache->games - $playerCache->wins,
                                    'totalGames' => $playerCache->games,
                                    'game' => $history->ladder->game,
                                    'url' => \App\URLHelper::getPlayerProfileUrl($history, $playerCache->player_name),
                                    'avatar' => $playerCache->player->user->getUserAvatar(),
                                    'twitch' => $playerCache->player->user->getTwitchProfile(),
                                    'youtube' => $playerCache->player->user->getYouTubeProfile(),
                                    'discord' => $playerCache->player->user->getDiscordProfile(),
                                ])
                            @endforeach
                        </div>

                        <div class="mt-5">
                            @include('components.pagination.paginate', ['paginator' => $players->appends(request()->query())])
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>

    @include('ladders.components.modal-ladder-rules')

@endsection

@if ($history->ends > Carbon\Carbon::now())
    @include('components.countdown', ['target' => $history->ends->toISO8601String()])
@endif
