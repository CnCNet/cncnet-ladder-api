<nav class="top-navigation navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/" title="CnCNet Home">
            <img src="/images/cncnet-logo.png" alt="CnCNet Logo" width="50px" />
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
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
