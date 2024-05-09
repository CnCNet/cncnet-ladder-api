<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-6 col-xl-3 mb-5 mb-xl-0">
                <h3 class="fw-bold">C&amp;C Games</h3>

                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/command-and-conquer">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('td') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Command &amp; Conquer</span>
                </a>
                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/red-alert">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('ra') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Red Alert</span>
                </a>
                <a class="footer-link d-flex text-decoration-none"href="https://cncnet.org/dune-2000">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('d2k') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Dune 2000</span>
                </a>
                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/tiberian-sun">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('ts') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Tiberian Sun</span>
                </a>
                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/red-alert-2">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('ra2') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Red Alert 2</span>
                </a>
                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/yuris-revenge">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('yr') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Yuri's Revenge</span>
                </a>

                {{-- @foreach (\App\Helpers\SiteHelper::getCnCNetSupportedCnCGames() as $game)
                    <a class="footer-link d-flex text-decoration-none" href="{{ $game->gamePage->page->slug }}">
                        <span class="game-icon game-icon-sm {{ $game->abbreviation }} me-2"
                            style="background-image:url('{{ Storage::url($game->icon_path) }}')"></span>
                        <span class="fw-bold me-3 game-icon-label">{{ $game->short_name }}</span>
                    </a>
                @endforeach --}}

            </div>

            <div class="col-12 col-sm-6 col-xl-3 mb-5 mb-xl-0">
                <h3>C&amp;C Mods</h3>

                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/dawn-of-the-tiberium-age">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('dta') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Dawn of the Tiberium Age</span>
                </a>
                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/mental-omega">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('mo') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Mental Omega</span>
                </a>
                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/cnc-reloaded">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('cncr') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">C&amp;C Reloaded</span>
                </a>
                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/rise-of-the-east">
                    <span class="game-icon game-icon-sm me-2"
                          style="background-image:url({{ \App\Models\URLHelper::getLadderIconByAbbrev('rote') }}"></span>
                    <span class="fw-bold me-3 game-icon-label">Rise of the East</span>
                </a>
            </div>

            <div class="col-12 mt-sm-5 mt-md-0 col-sm-6 col-xl-3 mb-5 mb-xl-0">
                <h3 class="fw-bold">Repair Bay</h3>

                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/discord" title="Discord">
                    <span class="game-icon game-icon-sm d-flex align-items-center">
                        <i class="bi bi-discord"></i>
                    </span>
                    <span class="fw-bold me-3 game-icon-label">CnCNet Discord</span>
                </a>

                <a class="footer-link d-flex text-decoration-none" href="https://forums.cncnet.org" title="Forums">
                    <span class="game-icon game-icon-sm d-flex align-items-center">
                        <i class="bi bi-chat-left"></i>
                    </span>
                    <span class="fw-bold me-3 game-icon-label">Forums</span>
                </a>

                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/faq" title="FAQs">
                    <span class="game-icon game-icon-sm d-flex align-items-center">
                        <i class="bi bi-patch-question"></i>
                    </span>
                    <span class="fw-bold me-3 game-icon-label">FAQs</span>
                </a>

                <a class="footer-link d-flex text-decoration-none" href="https://cncnet.org/status" title="Status">
                    <span class="game-icon game-icon-sm d-flex align-items-center">
                        <i class="bi bi-patch-question"></i>
                    </span>
                    <span class="fw-bold me-3 game-icon-label">CnCNet Status</span>
                </a>
            </div>

            <div class="col-12 mt-sm-5 mt-md-0 col-sm-6 col-xl-3">
                <h3 class="fw-bold">Support Us</h3>
                <p>By following and sharing our pages, you're spreading the news that C&amp;C is still alive!</p>

                <div class="d-flex flex-wrap">
                    <div>
                        <a href="http://facebook.com/cncnet" title="Follow CnCNet on Facebook" target="_blank" class="footer-social-link">
                            <i class="bi bi-facebook"></i>
                        </a>
                    </div>
                    <div>
                        <a href="http://twitter.com/cncnetofficial" title="Follow CnCNet on X" target="_blank" class="footer-social-link">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                    </div>
                    <div>
                        <a href="https://www.youtube.com/user/CnCNetOfficial?sub_confirmation=1" title="Subscribe to CnCNet on YouTube"
                           target="_blank" class="footer-social-link">
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                    <div>
                        <a href="https://reddit.com/r/cncnet" title="Subscribe to CnCNet on Reddit" target="_blank" class="footer-social-link">
                            <i class="bi bi-reddit"></i>
                        </a>
                    </div>
                    <div>
                        <a href="https://www.twitch.tv/cncnetofficial" title="Subscribe to CnCNet on Twitch" target="_blank"
                           class="footer-social-link">
                            <i class="bi bi-twitch"></i>
                        </a>
                    </div>
                    <div>
                        <a href="https://github.com/cncnet" title="Follow and Star us on GitHub" target="_blank" class="footer-social-link">
                            <i class="bi bi-github"></i>
                        </a>
                    </div>
                    <div>
                        <a href="https://cncnet.org/discord" title="Join our Discord" target="_blank" class="footer-social-link">
                            <i class="bi bi-discord"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <div class="row">
                <div class="col-sm-6 col-xl-4">
                    <h3 class="fw-bold">Open Collective</h3>
                    <p>
                        CnCNet runs on community support. All our finances are managed transparently using our Open Collective page.
                    </p>

                    <a href="https://opencollective.com/cncnet" class="btn btn-outline-primary" target="_blank" rel="nofollow">
                        Donate
                    </a>
                </div>

                <div class="col-sm-6 mt-5 mt-xl-0 col-xl-8 text-end">
                    <div>
                        <div class="footer-logos">
                            <div>
                                <img src="{{ Vite::asset('resources/images/logo.svg') }}" alt="CnCNet Logo" width="200">
                            </div>
                            <div class="mt-1">
                                <a href="http://gamesurge.net" target="_blank" title="GameSurge" rel="no-follow">
                                    <img src="{{ Vite::asset('resources/images/gamesurge.png') }}" alt="GameSurge Logo" width="80">
                                </a>
                            </div>
                        </div>

                        <div class="footer-copyright">
                            <a href="https://cncnet.org/privacy" title="Privacy policy" class="footer-link">
                                Privacy
                            </a>
                            <a href="https://cncnet.org/terms-conditions" title="Terms & Conditions" class="footer-link">
                                Terms
                            </a>
                            <div class="copyright-text">
                                Keeping C&C online since 2009 <br />
                                &copy; 2009 - {{ date('Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
