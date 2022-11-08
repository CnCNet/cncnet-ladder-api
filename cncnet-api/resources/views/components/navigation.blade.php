<nav class="top-navigation">
    <div class="container">
        <div class="nav-menu-items">
            <div class="nav-menu-header">
                <div class="nav-item nav-logo">
                    <a href="/"><img src="/images/cncnet-logo.png" alt="CnCNet Logo" /></a>
                </div>

                <button class="nav-menu-hamburger hamburger hamburger--collapse" data-toggle="collapse" data-target="#navigation-links" aria-expanded="false" id="menu">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </button>
            </div>

            <div class="nav-menu-links collapse navbar-collapse" id="navigation-links">
                <div class="spacer"></div>
                @include('components.navigation.ladders')
                <div class="spacer"></div>
                @include('components.navigation.online')
                <div class="nav-right nav-item">
                    @include('components.navigation.account')
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    (function() {

        var menu = document.getElementById("menu");
        console.log(menu);
        menu.addEventListener("click", function() {
            menu.classList.toggle("is-active");
        });
    })()
</script>
