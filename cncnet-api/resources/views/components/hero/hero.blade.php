<section class="hero {{ isset($subpage) ? 'hero-with-sub-page' : '' }}">
    <div class="feature-text">
        <div class="container">
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
        </div>
    </div>
</section>
