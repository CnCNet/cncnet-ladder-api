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
                <ul style="list-style:none;padding:0.5rem 0;margin-left:-0.5rem;">
                    @include('components.navigation.account')
                </ul>
            </div>
        </div>
    </div>
</div>
