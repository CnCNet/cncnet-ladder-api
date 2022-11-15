<nav class="top-navigation navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <div class="dropdown">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="/images/cncnet-logo.png" alt="CnCNet Logo" width="50px" />
            </a>
            <ul class="dropdown-menu text-small shadow" style="">
                <li><a class="dropdown-item" href="/">Ladder</a></li>
                <li><a class="dropdown-item" href="https://cncnet.org">CnCNet</a></li>
                <li><a class="dropdown-item" href="https://forums.cncnet.org">Forums</a></li>
                <li><a class="dropdown-item" href="https://cncnet.org/discord">Discord</a></li>
            </ul>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse position-relative align-items-center" id="mobileNav">
            <ul class="navbar-nav ms-auto me-5 align-items-center">
                @include('components.navigation.ladders')
                @include('components.navigation.online')
            </ul>

            <form role="search" class="me-4">
                <input type="search" class="form-control" placeholder="Search..." aria-label="Search">
            </form>

            @include('components.navigation.account')
        </div>
    </div>
</nav>
