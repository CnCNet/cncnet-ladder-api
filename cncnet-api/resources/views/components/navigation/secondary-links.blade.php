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
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/command-and-conquer">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('td') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">Command &amp; Conquer</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_td"></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/red-alert">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('ra') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">Red Alert</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_ra"></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/dune-2000">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('d2k') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">Dune 2000</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_d2k">
                                    < 10</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/tiberian-sun">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('ts') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">Tiberian Sun</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_ts"></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/yuris-revenge">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('yr') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">Yuri's Revenge</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_yr"></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/dawn-of-the-tiberium-age">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('dta') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">Dawn of the Tiberium Age</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_dta"></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/mental-omega">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('mo') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">Mental Omega</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_mo"></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/cnc-reloaded">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('cncr') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">C&amp;C Reloaded</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_cncr">
                                    < 10</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="https://cncnet.org/rise-of-the-east">
                                <span class="game-icon game-icon-sm"
                                    style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('rote') }}"></span>
                                <span class="fw-bold me-3 game-icon-label">Rise of the East</span>
                                <span class="badge badge-secondary ms-auto js-game-count-cncnet5_rote">
                                    < 10 </span>
                            </a>
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
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="http://cncnet.org/status" title="Status">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-graph-up"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">Status &amp; Tunnels</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="/community-guidelines-and-rules"
                        title="Community Guidelines &amp; Rules">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-journal-check"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">Community Guidelines &amp; Rules</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="https://discord.gg/grHVS7DZsJ" title="Report Private Concern">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-life-preserver"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">Report Private Concern</span>
                    </a>
                </li>
            </ul>
        </div>
    </li>
    <li class="nav-item d-none d-xl-flex m-xl-auto ms-1 mt-2 me-1">
        <div class="vr"></div>
    </li>
    @include('components.navigation.account')
</ul>
