<nav class="top-navigation navbar navbar-expand-lg navbar-dark">
    <div class="container">

        <div class="dropdown">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="/images/cncnet-logo.png" alt="CnCNet Logo" width="50px" />
            </a>
            <ul class="dropdown-menu text-small shadow" style="">
                <li><a class="dropdown-item active" href="#" aria-current="page">Overview</a></li>
                <li><a class="dropdown-item" href="#">Inventory</a></li>
                <li><a class="dropdown-item" href="#">Customers</a></li>
                <li><a class="dropdown-item" href="#">Products</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="#">Reports</a></li>
                <li><a class="dropdown-item" href="#">Analytics</a></li>
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
