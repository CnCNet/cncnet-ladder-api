@extends('layouts.app')
@section('title', $news->title)

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">{{ $news->title }}</x-slot>
        <x-slot name="description">
            {{ $news->description }}
        </x-slot>

        <div class="hero-btn-group">
            <a class="btn btn-outline-secondary me-3 btn-lg" href="/auth/register">Register</a>
            <a class="btn btn-outline-primary me-3 btn-lg" href="/auth/login">Login</a>
        </div>
    </x-hero-with-video>
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
                        <span class="d-none d-lg-flex">
                            {{ $news->title }}
                        </span>
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <div class="news-detail">
        <section class="pt-5 pb-5">
            <div class="container">
                <div class="news-detail-container">
                    @if ($news->getFeaturedImagePath())
                        <div class="news-feature">
                            <img src="{{ $news->getFeaturedImagePath() }}" alt="Featured image" />
                        </div>
                    @endif

                    <div class="news-meta">
                        <div class="me-4">
                            @include('components.avatar', ['avatar' => $news->getAuthor->getUserAvatar(), 'size' => 80])
                        </div>
                        <div>
                            <div class="author">Posted by {{ $news->getAuthor->name }}</div>
                            <div class="date">Published {{ $news->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div class="news-body mt-5">
                        {!! $news->body !!}
                    </div>
                </div>


                {{-- <div class="news-boxes news-boxes-swiper">
                    <div class="swiper js-news-listings">
                        <div class="swiper-wrapper">
                            @foreach ($news as $newsItem)
                                <div class="swiper-slide" data-swiper-autoplay="8000">
                                    <div class="swiper-slide" data-swiper-autoplay="8000">
                                        @include('news.components.news-box', ['newsItem' => $newsItem])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="me-auto">
                        <button class="btn swiper-btn-prev"><i class="bi bi-chevron-left"></i></button>
                        <button class="btn swiper-btn-next"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div> --}}
            </div>
        </section>
    </div>

    @if (count($moreNews) > 0)
        <div>
            <section class="pt-5 pb-5">
                <div class="container">

                    <h3>More news</h3>

                    <div class="news-boxes news-boxes-swiper">
                        <div class="swiper js-news-listings">
                            <div class="swiper-wrapper">
                                @foreach ($moreNews as $newsItem)
                                    <div class="swiper-slide" data-swiper-autoplay="8000">
                                        <div class="swiper-slide" data-swiper-autoplay="8000">
                                            @include('news.components.news-box', ['newsItem' => $newsItem])
                                        </div>
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
        </div>
    @endif
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
