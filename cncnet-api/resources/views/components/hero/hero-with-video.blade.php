<section class="hero hero-with-video {{ isset($subpage) ? 'hero-with-sub-page' : '' }}">
    <div class="feature-text">
        <div class="container">
            @if (isset($title) || isset($description))
                <div class="row">
                    <div class="col-12">
                        <h1 class="display-4 hero-title">
                            <strong class="fw-bold">
                                {{ $title }}
                            </strong>
                        </h1>

                        @if (isset($description))
                            <p class="hero-description">
                                {{ $description }}
                            </p>
                        @endif

                        @if (isset($slot))
                            <div class="mt-4 mb-4">
                                {{ $slot }}
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{ $slot }}
            @endif
        </div>
    </div>
    <div class="video">
        <video autoplay="true" loop="" muted="" preload="none" playsinline>
            <source src="{{ $attributes->get('video') }}" />
        </video>
    </div>
</section>
