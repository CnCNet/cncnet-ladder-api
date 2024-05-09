@props([
    'history',
    'games'
])
<div class="position-relative">
    <button class="btn swiper-btn-prev"><i class="bi bi-chevron-left"></i></button>
    <button class="btn swiper-btn-next"><i class="bi bi-chevron-right"></i></button>

    <div class="swiper js-game-listings">
        <div class="swiper-wrapper">
            @foreach ($games as $game)
                <div class="swiper-slide" data-swiper-autoplay="8000">

                    @if ($history->ladder->clans_allowed)
                        <x-ladder.listing.clan.game-box
                            :history="$history"
                            :game="$game"
                        />
                    @else
                        <x-ladder.listing.game-box
                            :history="$history"
                            :game="$game"
                        />
                    @endif
                </div>
            @endforeach
        </div>
        <div class="swiper-pagination"></div>
    </div>
</div>

@section('js')
    <script src="/js/swiper.js"></script>
    <script>
        var swiper = new Swiper(".js-game-listings", {
            slidesPerView: 3,
            spaceBetween: 30,
            loop: false,
            autoplay: false,
            breakpoints: {
                "@0.00": {
                    slidesPerView: 1,
                    spaceBetween: 10,
                },
                "@0.75": {
                    slidesPerView: 2,
                    spaceBetween: 20,
                },
                "@1.00": {
                    slidesPerView: 4,
                    spaceBetween: 40,
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
