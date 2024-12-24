@extends('layouts.app')
@section('title', $history->ladder->name)
@section('page-body-class', $history->ladder->abbreviation)

@section('feature')
    <x-hero-split>
        <x-slot name="subpage">true</x-slot>
        <x-slot name="video">{{ \App\Models\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation) }}</x-slot>
        <x-slot name="title">
            <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
            <span>Ladder Rankings</span>
        </x-slot>

        <x-slot name="description">
            Compete in <strong>1vs1</strong> or <strong>2vs2</strong> ranked matches with players all over the world.
        </x-slot>

        <x-slot name="logo">
            <img src="{{ \App\Models\URLHelper::getLadderLogoByAbbrev($history->ladder->abbreviation) }}" alt="{{ $history->ladder->name }}" />
        </x-slot>
    </x-hero-split>
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
        <div class="container-xl">
            @if ($history->ladder->qmLadderRules->tier2_rating > 0)
                <div class="league-selection">
                    <a href="{{ \App\Models\UrlHelper::getLadderLeague($history, 1) }}" title="{{ $history->ladder->name }}" class="league-box tier-1">
                        {!! \App\Helpers\LeagueHelper::getLeagueIconByTier(1) !!}
                        <h3 class="league-title">1vs1 - {{ \App\Helpers\LeagueHelper::getLeagueNameByTier(1) }}</h3>
                    </a>
                    <a href="{{ \App\Models\UrlHelper::getLadderLeague($history, 2) }}" title="{{ $history->ladder->name }}" class="league-box tier-2">
                        {!! \App\Helpers\LeagueHelper::getLeagueIconByTier(2) !!}
                        <h3 class="league-title">1vs1 - {{ \App\Helpers\LeagueHelper::getLeagueNameByTier(2) }}</h3>
                    </a>
                </div>
            @endif
        </div>
    </section>


    <section class="ladder-listing game-{{ $history->ladder->abbreviation }}">
        <div class="container-xl">

            @if ($history->ladder->abbreviation == 'blitz')
                <section class="useful-links d-md-flex mt-4">
                    <div class="me-3">
                        <a href="https://youtu.be/n_xWvNxO55c" class="btn btn-secondary btn-size-md" target="_blank">
                            <i class="bi bi-youtube pe-2"></i> How-To Play Blitz Online
                        </a>
                    </div>
                    <div>
                        <a href="https://youtu.be/EPDCaucx5qA" class="btn btn-secondary btn-size-md" target="_blank">
                            <i class="bi bi-youtube pe-2"></i> Tips & Tricks - New Blitz Players
                        </a>
                    </div>
                </section>
            @endif

            <section class="mt-4 ladder-info">
                <div>
                    <a href="/ladder/play" class="btn btn-secondary d-flex">
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
                        @foreach ($ladderHistoriesPrevious as $previous)
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

                <div>
                    <a id="canceledMatchesLink" href="/ladder/canceledMatches/{{ $history->ladder->abbreviation }}"
                                    class="btn btn-md btn-secondary mt-2">Canceled Matches</a>
                </div>
            </section>

            @include('ladders.listing._past-ladders', [
                'date' => \Carbon\Carbon::parse($history->ends),
                'search' => request()->search,
            ])

            @if (!request()->search)
                <section class="mt-5 mb-5">
                    <div class="row">
                        <div class="header">
                            <div class="col-md-12">
                                <h4>
                                    {{ \App\Helpers\SiteHelper::getLadderTypeFromHistory($history) }} Recent Games
                                    <small>
                                        <a href="{{ '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/games' }}">View
                                            All Games</a>
                                    </small>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <x-ladder.listing.recent-games :history="$history" :games="$games" />
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
                                    <input class="form-control border" name="search" value="{{ request()->search }}"
                                        placeholder="Search by Player..." />
                                </div>
                                @if (request()->search)
                                    <small>
                                        Searching for <strong>{{ request()->search }}</strong> returned {{ count($players) }}
                                        results
                                        <a href="?search=">Clear?</a>
                                    </small>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                @if (request()->tier == null || request()->tier == 1)
                    <h3 class="mt-2 mb-4">
                        Champions {{ \App\Helpers\SiteHelper::getLadderTypeFromHistory($history) }} League
                    </h3>
                @else
                    <h3 class="mt-2 mb-4">
                        <i class="bi bi-shield-slash-fill pe-3"></i>
                        Contenders {{ \App\Helpers\SiteHelper::getLadderTypeFromHistory($history) }} League
                    </h3>
                @endif

                @if ($players)
                    <x-ladder.listing.table :history="$history" :players="$players" :ranks="$ranks" :most-used-factions="$mostUsedFactions" :stats-x-of-the-day="$statsXOfTheDay" />
                    <div class="mt-5">
                        @include('components.pagination.paginate', ['paginator' => $players->appends(request()->query())])
                    </div>
                @endif

                @if ($clans)
                    <x-ladder.listing.clan.table :history="$history" :clans="$clans" :ranks="$ranks" :most-used-factions="$mostUsedFactions" :stats-x-of-the-day="$statsXOfTheDay" />

                    <div class="mt-5">
                        @include('components.pagination.paginate', ['paginator' => $clans->appends(request()->query())])
                    </div>
                @endif
            </section>
        </div>

    </section>
@endsection
