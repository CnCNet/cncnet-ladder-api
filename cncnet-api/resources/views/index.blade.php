@extends('layouts.app')
@section('title', 'Ladder')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Rankings</span>
                    </h1>

                    <p class="lead">
                        Compete in <strong>1vs1</strong> or <strong>2vs2</strong> ranked matches with players all over
                        the world.
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

@section('content')
    <div class="ladder-index">

        <section class="pt-5 pb-5">
            <div class="container-xl">
                <h2 class="mb-3">
                    <div class="icon-box me-2">
                        <span class="material-symbols-outlined color-green" style="font-size: 1.9rem">
                            feed
                        </span>
                    </div>
                    <strong>News</strong>
                </h2>

                <div class="news-boxes news-boxes-swiper">
                    <div class="swiper js-news-listings">
                        <div class="swiper-wrapper">
                            @foreach ($news as $newsItem)
                                <div class="swiper-slide" data-swiper-autoplay="8000">
                                    @include('news.components.news-box', ['newsItem' => $newsItem])
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="me-auto">
                        <button class="btn swiper-btn-prev"><i class="bi bi-chevron-left"></i></button>
                        <button class="btn swiper-btn-next"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </section>

        <section class="pt-3 pb-3">
            <div class="container-xl">
                <h2>
                    <div class="icon-box me-2">
                        <span class="material-symbols-outlined icon color-gold" style="font-size: 2rem">
                            military_tech
                        </span>
                    </div>
                    <strong>1vs1</strong> Ladders
                </h2>

                <div class="d-flex flex-wrap mt-4 player-ladders">
                    @foreach ($ladders as $history)
                        @include('components.ladder-box', [
                            'history' => $history,
                            'url' => \App\Models\URLHelper::getLadderUrl($history),
                        ])
                    @endforeach
                </div>
            </div>
        </section>

        <section class="pt-3 pb-3">
            <div class="container-xl">
                <h2>
                    <div class="icon-box me-2">
                        <i class="bi bi-flag-fill icon-clan" style="font-size: 1.5rem;"></i>
                    </div>
                    <strong>2vs2 Clan</strong> Ladders
                </h2>

                <div class="d-flex flex-wrap mt-4 clan-ladders">
                    @foreach ($clan_ladders as $history)
                        @if (!$history->ladder->private)
                            @include('components.ladder-box', [
                                'history' => $history,
                                'url' => \App\Models\URLHelper::getLadderUrl($history),
                            ])
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection

@section('js')
    <script src="/js/swiper.js"></script>
    <script>
        (function() {
            var swiper = new Swiper(".js-news-listings", {
                slidesPerView: 3,
                spaceBetween: 10,
                loop: false,
                autoplay: false,
                breakpoints: {
                    "@0.00": {
                        slidesPerView: 1,
                    },
                    "@0.75": {
                        slidesPerView: 2,
                    },
                    "@1.00": {
                        slidesPerView: 2,
                    },
                },
                // Navigation arrows
                navigation: {
                    nextEl: '.swiper-btn-next',
                    prevEl: '.swiper-btn-prev',
                },
            });
        })();
    </script>
@endsection
