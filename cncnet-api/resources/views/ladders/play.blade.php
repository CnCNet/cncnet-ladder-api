@extends('layouts.app')
@section('head')
    <script src="/js/chart.min.js"></script>
    <script src="/js/chartjs-adapter-date-fns.bundle.min.js"></script>
@endsection

@section('title', 'Ladder')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <span>Popular Times To play</span>
                    </h1>

                    <p class="lead">
                        Find the most popular hour of the day to play ranked matches.
                    </p>

                    @if (!\Auth::user())
                        <div class="mt-4">
                            <a class="btn btn--outline-primary me-3 btn-size-lg" href="/auth/register">Register</a>
                            <a class="btn btn--outline-secondary btn-size-lg" href="/auth/login">Login</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('breadcrumb')
@endsection

@section('content')
    <div class="ladder-index">
        <section class="pt-5 pb-5">
            <div class="container">
                <h3>
                    <span class="material-symbols-outlined icon">
                        military_tech
                    </span>
                    <strong>1vs1</strong> Ranked Matches - Popular hours to play
                </h3>

                <p class="lead">
                    Below is a 24 hour graph showing what time of day is most popular to play ranked matches.
                    <br />
                    Data is taken from the current and previous months ladder games.
                </p>

                <p class="lead">
                    <strong>Times are based in UTC.</strong>
                </p>

                <div class="col-10 mt-5 m-auto">
                    <canvas id="gamesPlayed" width="450" height="340" style="margin-top: 15px;"></canvas>
                </div>
            </div>
        </section>
    </div>

    <script>
        const ctx = document.getElementById("gamesPlayed");
        const data = {
            labels: {!! json_encode($labels) !!},
            datasets: [
                <?php foreach($games as $game => $data): ?>
                <?php
                $showGame = false;
                if ($filteredGame == $game) {
                    $showGame = true;
                }
                if ($filteredGame == null) {
                    $showGame = true;
                }
                ?>
                <?php $l = \App\Ladder::where('abbreviation', $game)->first(); ?> {
                    label: "{!! $l->name !!}",
                    data: {!! json_encode($data[0]) !!},
                    fill: true,
                    backgroundColor: "{!! \App\Helpers\ChartHelper::getChartColourByGameAbbreviation($game, 0.1) !!}",
                    borderColor: "{!! \App\Helpers\ChartHelper::getChartColourByGameAbbreviation($game) !!}",
                    tension: 0.1,
                    hidden: {{ $showGame ? 'false' : 'true' }}
                },
                <?php endforeach; ?>
            ]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
            }
        };

        new Chart(ctx, config);
    </script>
@endsection
