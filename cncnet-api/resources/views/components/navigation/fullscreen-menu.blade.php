<div class="fullscreen-nav" id="fullscreenNav" aria-labelledby="fullscreenNavLabel">
    <div class="container-fluid">

        <div class="fs-close-header">
            <button class="btn-close " type="button" data-bs-toggle="offcanvas" data-bs-target="#fullscreenNav" aria-controls="fullscreenNav"
                aria-expanded="false" aria-label="Toggle navigation">
            </button>
        </div>

        <div class="fs-menu-container container">

            <div class="fs-menu-category">
                <div class="fs-menu-category-links">
                    <a class="fs-menu-link" href="https://cncnet.org/discord">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-discord"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">CnCNet Discord</span>
                    </a>
                    <a class="fs-menu-link" href="https://forums.cncnet.org">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-chat-left"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">Forums</span>
                    </a>
                    <a class="fs-menu-link" href="https://cncnet.org/faq">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-patch-question"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">FAQ's</span>
                    </a>
                    <a class="fs-menu-link" href="https://cncnet.org/buy">
                        <span class="game-icon game-icon-sm d-flex align-items-center">
                            <i class="bi bi-patch-question"></i>
                        </span>
                        <span class="fw-bold me-3 game-icon-label">Where to buy C&C</span>
                    </a>
                </div>
            </div>

            {{-- @php $supportedGames = \App\Helpers\SiteHelper::getCnCNetSupportedCnCGames(); @endphp
            @if (count($supportedGames) > 0)
                <div class="fs-menu-category">
                    <div class="fs-menu-category-name">
                        <span class="title">
                            Supported C&amp;C Games
                        </span>
                        <span class="description">
                            View Downloads &amp; How to play
                        </span>
                    </div>

                    <div class="fs-menu-category-links">
                        @foreach ($supportedGames as $game)
                            <a class="fs-menu-link" href="{{ $game->gamePage->page->slug }}">
                                <span class="game-icon game-icon-sm {{ $game->abbreviation }}"
                                    style="background-image:url('{{ Storage::url($game->icon_path) }}')"></span>
                                <span class="fw-bold ms-3 me-3 game-icon-label">{{ $game->short_name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif --}}

            {{-- @php $supportedMods = \App\Helpers\SiteHelper::getCnCNetSupportedMods(); @endphp
            @if (count($supportedMods) > 0)
                <div class="fs-menu-category">
                    <div class="fs-menu-category-name">
                        <div>
                            Supported C&amp;C Mods
                            <span class="description">
                                View Downloads &amp; How to play
                            </span>
                        </div>
                    </div>

                    <div class="fs-menu-category-links">
                        @foreach ($supportedMods as $game)
                            <a class="fs-menu-link" href="{{ $game->gamePage->page->slug }}">
                                <span class="game-icon game-icon-sm {{ $game->abbreviation }}"
                                    style="background-image:url('{{ Storage::url($game->icon_path) }}')"></span>
                                <span class="fw-bold ms-3 me-3 game-icon-label">{{ $game->short_name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif --}}

            {{-- @php $otherGames = \App\Helpers\SiteHelper::getOtherCnCGames(); @endphp
            @if (count($otherGames) > 0)
                <div class="fs-menu-category">
                    <div class="fs-menu-category-name">
                        <div>
                            Other C&amp;C Games
                            <span class="description">
                                How to play the rest of the C&amp;C Games
                            </span>
                        </div>
                    </div>

                    <div class="fs-menu-category-links">
                        @foreach ($otherGames as $game)
                            <a class="fs-menu-link" href="{{ route($game->abbreviation) }}">
                                <span class="game-icon game-icon-sm {{ $game->abbreviation }}"></span>
                                <span class="fw-bold ms-3 me-3 game-icon-label">{{ $game->short_name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif --}}
        </div>
    </div>
</div>
