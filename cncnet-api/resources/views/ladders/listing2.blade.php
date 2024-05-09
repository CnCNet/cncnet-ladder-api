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
        <div class="container">
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
        <div class="container">

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
            </section>

                @include('ladders.listing._past-ladders', [
                    'date' =>  \Carbon\Carbon::parse($history->ends),
                    'search' => request()->search,
                ])


                @if (!request()->search)
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
                                            <a href="{{ '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/games' }}">View
                                                All Games</a>
                                        </small>
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <x-ladder.recent-games :history="$history" :games="$games" />
                    </section>
                @endif

        </div>


    </section>


@endsection