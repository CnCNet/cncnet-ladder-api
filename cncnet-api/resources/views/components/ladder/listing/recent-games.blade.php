@props(['history', 'games'])
<div class="position-relative">
    <button class="btn swiper-btn-prev"><i class="bi bi-chevron-left"></i></button>
    <button class="btn swiper-btn-next"><i class="bi bi-chevron-right"></i></button>

    <div class="swiper js-game-listings">
        <div class="swiper-wrapper">
            @foreach ($games as $game)
                <div class="swiper-slide" data-swiper-autoplay="8000">
                    <x-ladder.listing.game-box :history="$history" :game="$game" />
                </div>
            @endforeach
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-scrollbar"></div>
        <div class="autoplay-progress">
            <svg viewBox="0 0 48 48">
                <circle cx="24" cy="24" r="20"></circle>
            </svg>
            <span></span>
        </div>
    </div>
</div>

@section('js')
    <script src="/js/swiper.js"></script>
    <script>
        const progressCircle = document.querySelector(".autoplay-progress svg");
        const progressContent = document.querySelector(".autoplay-progress span");
        const swiper = new Swiper(".js-game-listings", {
            scrollbar: {
                el: ".swiper-scrollbar",
            },
            slidesPerView: 3,
            freeMode: true,
            spaceBetween: 30,
            loop: false,
            autoplay: {
                delay: 6500,
                disableOnInteraction: false
            },
            on: {
                autoplayTimeLeft(s, time, progress) {
                    progressCircle.style.setProperty("--progress", 1 - progress);
                    progressContent.textContent = `${Math.ceil(time / 1000)}s`;
                }
            },
            breakpoints: {
                "@0.00": {
                    slidesPerView: 1,
                    spaceBetween: 10,
                },
                "@0.75": {
                    slidesPerView: 2,
                    spaceBetween: 15,
                },
                "@1.00": {
                    slidesPerView: 4,
                    spaceBetween: 15,
                },
                "@1.25": {
                    slidesPerView: 5,
                    spaceBetween: 10,
                },
                "@2.00": {
                    slidesPerView: 6,
                    spaceBetween: 10,
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
