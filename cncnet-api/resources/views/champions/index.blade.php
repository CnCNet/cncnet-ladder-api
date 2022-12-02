@extends('layouts.app')
@section('title', 'League Champions')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev($abbreviation))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev($abbreviation))

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="{{ \App\URLHelper::getLadderLogoByAbbrev($abbreviation) }}" class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $ladder->name }}</strong> <br />
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
            @foreach ($ladders_winners as $ladderWinners)
                <div>
                    <div class="mb-2">
                        <h2 class="pb-2 pt-5" style="color:#bbb">
                            {{ $ladderWinners['history']['ends']->format('F Y') }} - <strong>Ladder Champions</strong>
                        </h2>

                        <a href="{{ \App\URLHelper::getLadderUrl($ladderWinners['history']) }}" class="btn btn-secondary">
                            View Full {{ $ladderWinners['history']['ends']->format('F Y') }} ladder
                        </a>
                    </div>

                    @include('ladders.listing._ladder-table', [
                        'players' => $ladderWinners['players'],
                        'history' => $ladderWinners['history'],
                        'sides' => $ladderWinners['sides'],
                    ])
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
