<div class="position-relative">
    <button class="btn swiper-btn-prev"><i class="bi bi-chevron-left"></i></button>
    <button class="btn swiper-btn-next"><i class="bi bi-chevron-right"></i></button>

    <div class="swiper js-game-listings">
        <div class="swiper-wrapper">
            @foreach ($games as $game)
                <div class="swiper-slide">
                    <?php $pp = $game->playerGameReports()->first(); ?>

                    @include('components/game-box', [
                        'url' => '/ladder/' . $history->short . '/' . $history->ladder->abbreviation . '/games/' . $game->id,
                        'game' => $history->ladder->abbreviation,
                        'gamePlayers' => $game->playerGameReports(),
                        'gameReport' => $game->report()->first(),
                        'status' => isset($pp) ? ($pp->won ? 'won' : 'lost') : '',
                        'points' => $pp,
                        'map' => $game->hash,
                        'title' => $game->scen,
                        'date' => $game->created_at,
                    ])
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
            slidesPerView: 4,
            spaceBetween: 30,
            autoplay: {
                delay: 2500,
                disableOnInteraction: true,
            },
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
