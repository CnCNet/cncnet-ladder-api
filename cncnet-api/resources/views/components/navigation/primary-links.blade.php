<ul class="navbar-nav me-auto mb-2 mb-lg-0">

    <li class="nav-item dropdown" data-bs-hover="dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{ __('Downloads') }}
        </a>
        <div class="dropdown-menu columns-2">
            <div class="row">
                <div class="col-12 col-xl-6">
                    <ul class="list-unstyled">
                        <li>
                            <div class="dropdown-label-item">
                                <div class="d-flex align-items-center">
                                    <div>
                                        Supported C&amp;C Games
                                        <span class="dropdown-label-item-description">
                                            View Downloads &amp; How to play
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </li>

                        @include('components.navigation._games')

                    </ul>
                </div>
                <div class="col-12 col-xl-6">
                    <ul class="list-unstyled">
                        <li>
                            <div class="dropdown-label-item">
                                <div class="d-flex align-items-center">
                                    <div>
                                        Supported C&amp;C Mods
                                        <span class="dropdown-label-item-description">
                                            View Downloads &amp; How to play
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @include('components.navigation._mods')
                    </ul>
                </div>
            </div>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="https://forums.cncnet.org" title="{{ __('Forums') }}">{{ __('Forums') }}</a>
    </li>
    <li class="nav-item dropdown" data-bs-hover="dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{ __('Ladders') }}
        </a>
        <div class="dropdown-menu columns-2">
            <div class="row">
                <div class="col-12 col-xl-6">
                    <ul class="list-unstyled">
                        <li>
                            <div class="dropdown-label-item">
                                <div class="d-flex align-items-center">
                                    <div>
                                        Ranked Ladders
                                        <span class="dropdown-label-item-description">
                                            Compete in 1vs1 or 2vs2 ranked matches
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @if (isset($ladders))
                            @include('components.navigation.ladders')
                        @endif
                    </ul>
                </div>
                <div class="col-12 col-xl-6">
                    <ul class="list-unstyled">
                        <li>
                            <div class="dropdown-label-item">
                                <div class="d-flex align-items-center">
                                    <div>
                                        Hall of Fame
                                        <span class="dropdown-label-item-description">
                                            View Past champions
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @if (isset($ladders))
                            @include('components.navigation.hof')
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link live-link" href="https://cncnet.org/live" title="{{ __('Live') }}">
            {{ __('Watch') }} <span class="live-count-badge text-uppercase"> {{ __('live') }}</span>
        </a>
    </li>
    <li class="nav-item d-none d-xl-flex m-xl-auto ms-1 mt-2 me-1">
        <div class="vr"></div>
    </li>
    {{-- <li class="nav-item dropdown" data-bs-hover="dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{ __('Explore C&C') }}
        </a>
        <div class="dropdown-menu columns-1">
            <div class="row">
                <div class="col-12 col-xl-12">
                    <ul class="list-unstyled">
                        <li>
                            <div class="dropdown-label-item">
                                <div class="d-flex align-items-center">
                                    <div>
                                        Other C&amp;C Games
                                        <span class="dropdown-label-item-description">
                                            How to play the rest of the C&amp;C Games
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @foreach (\App\Helpers\SiteHelper::getOtherCnCGames() as $game)
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route($game->abbreviation) }}">
                                    <span class="game-icon game-icon-sm {{ $game->abbreviation }}"></span>
                                    <span class="fw-bold me-3 game-icon-label">{{ $game->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </li> --}}
    <li class="nav-item nav-hide-xl">
        <a class="nav-link" href="https://cncnet.org/buy" title="{{ __('Buying C&C') }}">
            {{ __('Where to buy C&C') }}
        </a>
    </li>
</ul>
