@extends('layouts.app')
@section('title', $history->ladder->name)
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev($history->ladder->abbreviation))

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
                        <span>Ladder Rankings</span>
                    </h1>

                    <p class="lead text-uppercase">
                        <small>{{ $history->starts->format('F Y') }} -
                            <strong>
                                @if ($history->ladder->clans_allowed)
                                    2 vs 2 Ranked Match
                                @else
                                    1 vs 1 Ranked Match
                                @endif
                            </strong>
                        </small>
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
                        {{ $history->ladder->name }}
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-3 pb-3">
        <div class="container">
            @if ($history->ladder->qmLadderRules->tier2_rating > 0)
                <div class="league-selection">
                    <a href="{{ \App\UrlHelper::getLadderLeague($history, 1) }}" title="{{ $history->ladder->name }}" class="league-box tier-1">
                        {!! \App\Helpers\LeagueHelper::getLeagueIconByTier(1) !!}
                        <h3 class="league-title">1vs1 - {{ \App\Helpers\LeagueHelper::getLeagueNameByTier(1) }}</h3>
                    </a>
                    <a href="{{ \App\UrlHelper::getLadderLeague($history, 2) }}" title="{{ $history->ladder->name }}" class="league-box tier-2">
                        {!! \App\Helpers\LeagueHelper::getLeagueIconByTier(2) !!}
                        <h3 class="league-title">1vs1 - {{ \App\Helpers\LeagueHelper::getLeagueNameByTier(2) }}</h3>
                    </a>
                </div>
            @endif

        </div>
    </section>

    <section class="ladder-listing game-{{ $history->ladder->abbreviation }}">
        <div class="container">
            @if ($history->ladder->abbreviation == 'blitz')
                <section class="useful-links d-md-flex mt-4">
                    <div class="me-3">
                        <a href="https://youtu.be/n_xWvNxO55c" class="btn btn-primary btn-size-md" target="_blank">
                            <i class="bi bi-youtube pe-2"></i> How-To Play Blitz Online
                        </a>
                    </div>
                    <div>
                        <a href="https://youtu.be/EPDCaucx5qA" class="btn btn-primary btn-size-md" target="_blank">
                            <i class="bi bi-youtube pe-2"></i> Tips & Tricks - New Blitz Players
                        </a>
                    </div>
                </section>
            @endif
            <section class="mt-4 ladder-info">
                <div>
                    <a href="/ladder/play" class="btn btn-secondary d-flex"">
                        <span class="material-symbols-outlined pe-3">
                            schedule
                        </span> Popular Times
                    </a>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary d-flex" data-bs-toggle="modal" data-bs-target="#openLadderRules">
                        <span class="material-symbols-outlined pe-3">
                            gavel
                        </span>
                        Rules
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
                        <span class="material-symbols-outlined icon me-2">
                            hotel_class
                        </span>
                        Hall of Fame
                    </button>
                    <ul class="dropdown-menu">
                        @foreach ($ladders_previous as $previous)
                            <li>
                                <a href="/ladder/{{ $previous->short . '/' . $previous->ladder->abbreviation }}/" title="{{ $previous->ladder->name }}"
                                    class="dropdown-item">
                                    {{ $previous->starts->format('Y - F') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="mt-5 mb-5">
                @include('ladders.listing._qm-stats', [
                    'stats' => $stats,
                    'history' => $history,
                    'statsXOfTheDay' => $statsXOfTheDay,
                ])
            </section>

            @include('ladders.listing._past-ladders')

            @if (!$search)
                <section class="mt-5 mb-5">
                    <div class="row">
                        <div class="header">
                            <div class="col-md-12">
                                <h4>
                                    @if ($history->ladder->clans_allowed)
                                        <strong>Clan</strong>
                                    @else
                                        <strong>1vs1</strong>
                                    @endif
                                    Recent Games
                                    <small>
                                        <a href="{{ '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/games' }}">View All Games</a>
                                    </small>
                                </h4>
                            </div>
                        </div>
                    </div>
                    @include('ladders.listing._recent-games', ['games' => $games])
                </section>
            @endif

            <section>
                @if (request()->input('filterBy') == 'games')
                    <p>
                        You are ordering by game count, <a href="?#listing">reset by rank?</a>
                    </p>
                @endif

                <div class="d-flex flex-column d-sm-flex flex-sm-row">
                    @if ($players)
                        @include('components.pagination.paginate', ['paginator' => $players->appends(request()->query())])
                    @endif

                    @if ($clans)
                        @include('components.pagination.paginate', ['paginator' => $clans->appends(request()->query())])
                    @endif

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

                @if ($tier == null || $tier == 1)
                    <h3 class="mt-2 mb-4">
                        @if ($isClanLadder)
                            <i class="bi bi-flag-fill icon-clan pe-3"></i>
                            Champions Clan League
                        @else
                            <i class="bi bi-trophy pe-3"></i>
                            1vs1 - Champions Players League
                        @endif
                    </h3>
                @else
                    <h3 class="mt-2 mb-4">
                        <i class="bi bi-shield-slash-fill pe-3"></i>
                        @if ($isClanLadder)
                            Contenders Clan League
                        @else
                            1vs1 - Contenders Players League
                        @endif
                    </h3>
                @endif

                @if ($players)
                    @include('ladders.listing._ladder-table', [
                        'players' => $players, 
                        'ladderHasEnded' => $history->hasEnded(),
                        'statsXOfTheDay' => $statsXOfTheDay
                    ])
                    
                    <div class="mt-5">
                        @include('components.pagination.paginate', ['paginator' => $players->appends(request()->query())])
                    </div>
                @endif

                @if ($clans)
                    @include('ladders.listing.clan._ladder-table', [
                        'clans' => $clans, 
                        'ladderHasEnded' => $history->hasEnded(),
                        'statsXOfTheDay' => $statsXOfTheDay
                    ])

                    <div class="mt-5">
                        @include('components.pagination.paginate', ['paginator' => $clans->appends(request()->query())])
                    </div>
                @endif
            </section>
        </div>
    </section>
    @include('ladders.listing._modal-ladder-rules')
@endsection
