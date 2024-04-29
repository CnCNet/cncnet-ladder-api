<section class="hero {{ isset($video) ? 'hero-with-video' : '' }} {{ isset($subpage) ? 'hero-with-sub-page' : '' }}">
    <div class="feature-text">
        <div class="container">

            <div class="row">
                <div class="col-12 col-xl-7 order-2 order-xl-1">
                    <h1 class="hero-title">
                        <strong class="fw-bold">
                            {{ $title }}
                        </strong>
                    </h1>

                    @if (isset($description))
                        <p class="fw-bold hero-description">
                            {{ $description }}
                        </p>
                    @endif

                    @if (isset($slot))
                        <div class="mt-4 mb-4">
                            {{ $slot }}
                        </div>
                    @endif
                </div>

                <div class="col-12 col-xl-5 text-xl-left text-xxl-right text-xl-end order-1 order-xl-2 mb-4 mg-xl-0 game-logo">
                    @if (isset($logo))
                        {{ $logo }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (isset($video))
        <div class="video">
            <video autoplay="true" loop="" muted="" preload="none" playsinline>
                <source src="{{ $video }}" />
            </video>
        </div>
    @endif
</section>
