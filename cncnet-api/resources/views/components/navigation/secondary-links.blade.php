<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
    <li class="nav-item dropdown">
        <a class="nav-link highlight dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="js-game-count-total"></span> Players Online
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            <div class="row">
                <div class="col-12">
                    <ul class="list-unstyled">
                        <li>
                            <div class="dropdown-label-item">
                                <div class="d-flex align-items-center">
                                    <a href="https://cncnet.org/status" title="CnCNet Status" class="text-decoration-none">
                                        Online CnCNet
                                        <span class="dropdown-label-item-description">
                                            Join the <span class="js-game-count-total"></span> players online right now
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </li>
                        {{-- @foreach (\App\Helpers\SiteHelper::getCnCNetSupportedCnCGames() as $game)
                            @if ($game->abbreviation != 'ra2')
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="{{ url($game->gamePage->page->slug) }}">
                                        <span class="game-icon game-icon-sm {{ $game->abbreviation }}"
                                            style="background-image:url('{{ Storage::url($game->icon_path) }}')"></span>
                                        <span class="fw-bold me-3 game-icon-label">{{ $game->short_name }}</span>
                                        <span class="badge badge-secondary ms-auto js-game-count-cncnet5_{{ $game->abbreviation }}">X</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach --}}
                        {{-- @foreach (\App\Helpers\SiteHelper::getCnCNetSupportedMods() as $mod)
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ url($mod->gamePage->page->slug) }}">
                                    <span class="game-icon game-icon-sm {{ $mod->abbreviation }}"
                                        style="background-image:url('{{ Storage::url($mod->icon_path) }}')"></span>
                                    <span class="fw-bold me-3 game-icon-label">{{ $mod->short_name }}</span>
                                    <span class="badge badge-secondary ms-auto js-game-count-cncnet5_{{ $mod->abbreviation }}">X</span>
                                </a>
                            </li>
                        @endforeach --}}
                    </ul>
                </div>
            </div>
        </div>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{ __('Support') }}
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            <ul class="list-unstyled">
                <li>
                    <div class="dropdown-label-item">
                        <div class="d-flex align-items-center">
                            <div>
                                Help Center
                                <span class="dropdown-label-item-description">
                                    Installation issues, connection problems?
                                </span>
                            </div>
                        </div>
                    </div>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/discord" title="Discord">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-discord"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">CnCNet Discord</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="https://forums.cncnet.org" title="Forums">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-chat-left"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">Forums</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/faq" title="FAQs">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-patch-question"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">FAQs</span>
                    </a>
                </li>
            </ul>
        </div>
    </li>
    {{-- <li class="nav-item d-none d-xl-flex m-xl-auto ms-1 mt-2 me-1">
        <div class="vr"></div>
    </li>
    <li class="nav-item m-xl-auto">
        <a class="nav-link disabled" href="explore-cnc" title="{{ __('Explore C&C') }}">
            <i class="bi bi-person-circle "></i>
            {{ __('Sign In') }}
        </a>
    </li> --}}
</ul>
