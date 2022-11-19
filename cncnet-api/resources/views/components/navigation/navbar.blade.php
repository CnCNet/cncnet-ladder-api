<nav class="top-navigation navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <div class="btn-group">
            <a href="/" class="btn pe-1 ps-0">
                <img src="/images/cncnet-logo.png" alt="CnCNet Logo" width="55px" />
            </a>

            <button type="button" class="btn dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>

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
                <li><a class="dropdown-item" href="https://ladder.cncnet.org/donate">Donate to Ladder Devs</a></li>
            </ul>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse position-relative align-items-center" id="mobileNav">
            <ul class="navbar-nav ms-auto align-items-center">
                @include('components.navigation.ladders')
                @include('components.navigation.online')
                @include('components.navigation.account')
            </ul>
        </div>
    </div>
</nav>
