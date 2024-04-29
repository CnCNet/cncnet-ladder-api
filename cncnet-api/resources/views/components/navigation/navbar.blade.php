<nav class="navbar navbar-main navbar-expand-xxl fixed-top js-navbar">
    <div class="container-fluid">
        <div class="btn-group">
            <a class="navbar-brand" href="{{ url('/') }}" title="CnCNet Home">
                <img src="{{ Vite::asset('resources/images/logo.svg') }}" alt="CnCNet logo" loading="lazy" class="logo-full" />
            </a>

            <button class="navbar-toggler hamburger hamburger--collapse" type="button" data-bs-toggle="offcanvas" data-bs-target="#fullscreenNav"
                aria-controls="fullscreenNav" aria-expanded="false" aria-label="Toggle navigation">
                <div class="hamburger-box">
                    <div class="hamburger-inner"></div>
                </div>
            </button>
        </div>

        <div class="navbar-collapse collapse" id="navbarSupportedContent">
            @include('components.navigation.primary-links')
            @include('components.navigation.secondary-links')
        </div>
        {{-- 
            <ul class="dropdown-menu">
                <li>
                    <h4 class="dropdown-header text-uppercase pt-2">CnCNet - Play C&C Online</h4>
                </li>
                <li><a class="dropdown-item" href="https://cncnet.org/download">Games</a></li>
                <li><a class="dropdown-item" href="https://forums.cncnet.org">Forums</a></li>
                <li><a class="dropdown-item" href="https://cncnet.org/discord">Discord</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="https://cncnet.org/live">Live</a></li>
                <li><a class="dropdown-item highlight" href="https://ladder.cncnet.org/news">News</a></li>
            </ul> --}}

        {{-- <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav" aria-controls="mobileNav"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse position-relative align-items-center" id="mobileNav">
            <ul class="navbar-nav ms-auto align-items-center">
                @include('components.navigation.hof')
                @include('components.navigation.ladders')
                @include('components.navigation.online')
                @include('components.navigation.account')
            </ul>
        </div> --}}
    </div>
</nav>
