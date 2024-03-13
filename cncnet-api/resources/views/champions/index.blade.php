@extends('layouts.app')
@section('title', 'League Champions')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev($abbreviation))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev($abbreviation))

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="{{ \App\Models\URLHelper::getLadderLogoByAbbrev($abbreviation) }}"
                         class="d-block img-fluid me-lg-0 ms-lg-auto"/>
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $ladder->name }}</strong> <br/>
                        <span>Ladder Hall of Fame</span>
                    </h1>

                    <p class="lead text-uppercase">
                        Winners of previous months in <strong>1 vs 1 Ranked Match</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="ladder-listing game-{{ $abbreviation }}">
        <div class="container">

            @if ($isTierLeague)
                <div class="league-selection pt-2">
                    <a href="{{ \App\Models\UrlHelper::getLadderChampionsUrl($abbreviation, 1) }}"
                       class="league-box tier-1">
                        {!! \App\Helpers\LeagueHelper::getLeagueIconByTier(1) !!}
                        <h3 class="league-title">1vs1 - {{ \App\Helpers\LeagueHelper::getLeagueNameByTier(1) }}</h3>
                    </a>
                    <a href="{{ \App\Models\UrlHelper::getLadderChampionsUrl($abbreviation, 2) }}"
                       class="league-box tier-2">
                        {!! \App\Helpers\LeagueHelper::getLeagueIconByTier(2) !!}
                        <h3 class="league-title">1vs1 - {{ \App\Helpers\LeagueHelper::getLeagueNameByTier(2) }}</h3>
                    </a>
                </div>
            @endif

            @foreach ($ladders_winners as $ladderWinners)
                <div>
                    <div class="mb-2">

                        @if (request()->tier == null || request()->tier == 1)
                            <h3 class="mt-4 mb-4">
                                @if ($isClanLadder)
                                    <i class="bi bi-flag-fill icon-clan pe-3"></i>
                                    {{ $ladderWinners['history']['ends']->format('F Y') }} Champions Clan League
                                @else
                                    <i class="bi bi-trophy pe-3"></i>
                                    {{ $ladderWinners['history']['ends']->format('F Y') }} 1vs1 - Champions Players
                                    League
                                @endif
                            </h3>
                        @else
                            <h3 class="mt-4 mb-4">
                                <i class="bi bi-shield-slash-fill pe-3"></i>
                                @if ($isClanLadder)
                                    {{ $ladderWinners['history']['ends']->format('F Y') }} Contenders Clan League
                                @else
                                    {{ $ladderWinners['history']['ends']->format('F Y') }} 1vs1 - Contenders Players
                                    League
                                @endif
                            </h3>
                        @endif

                        <a href="{{ \App\Models\URLHelper::getLadderUrl($ladderWinners['history']) }}?tier={{ request()->tier }}"
                           class="btn btn-secondary">
                            View Full {{ $ladderWinners['history']['ends']->format('F Y') }} ladder
                        </a>
                    </div>

                    @if ($ladderWinners['players'])
                        @include('ladders.listing._ladder-table', [
                            'players' => $ladderWinners['players'],
                            'history' => $ladderWinners['history'],
                            'sides' => $ladderWinners['sides'],
                            'ladderHasEnded' => true,
                        ])
                    @endif

                    @if ($ladderWinners['clans'])
                        @include('ladders.listing.clan._ladder-table', [
                            'clans' => $ladderWinners['clans'],
                            'history' => $ladderWinners['history'],
                            'sides' => $ladderWinners['sides'],
                            'ladderHasEnded' => true,
                        ])
                    @endif
                </div>
            @endforeach
        </div>
    </section>
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
                            hotel_class
                        </span>
                        Ladders Hall of Fame
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection
