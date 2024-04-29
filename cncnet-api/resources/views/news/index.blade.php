@extends('layouts.app')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev('ra2'))
@section('title', 'CnCNet News')

@section('feature')
    <div class="feature pt-3 pb-3">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3">
                        <strong>CnCNet News</strong>
                    </h1>

                    <div class="mini-breadcrumb d-none d-lg-flex">
                        <div class="mini-breadcrumb-item">
                            <a href="/" title="Home">
                                <span class="material-symbols-outlined">
                                    home
                                </span>
                            </a>
                        </div>
                        <div class="mini-breadcrumb-item">
                            <a href="/news" title="News">
                                <span class="material-symbols-outlined">
                                    news
                                </span>
                                News
                            </a>
                        </div>
                    </div>
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
                <li class="breadcrumb-item">
                    <a href="/admin/news/create">
                        <span class="material-symbols-outlined pe-3">
                            feed
                        </span>
                        News
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <div class="news-detail">
        <div class="container">
            <div class="news-boxes">
                @foreach ($news as $newsItem)
                    @include('news.components.news-box', ['newsItem' => $newsItem])
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="/js/swiper.js"></script>
    <script>
        var swiper = new Swiper(".js-news-listings", {
            slidesPerView: 1,
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
                    slidesPerView: 4,
                },
            },
            // Navigation arrows
            navigation: {
                nextEl: '.swiper-btn-next',
                prevEl: '.swiper-btn-prev',
            },
        });
    </script>
@endsection
