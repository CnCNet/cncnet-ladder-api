@php $bodyClass = ""; @endphp
@hasSection('body-feature-image')
@php $bodyClass = "body-feature-image"; @endphp
@endif

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords"
        content="C&amp;C, Command and Conquer, C&amp;C95, C&quot;C1, RA, RA95, CnCNet, Online, C&amp;C95 Online,
        C&amp;C GLE, LANmate, Tiberian, Sun, Tiberium, Red Alert, Red, Alert, Kane, Stalin, Classics, Forums, website, online,
        chat, GDI, Nod, The Brotherhood of Nod, The Global Defense Initiative, Allies, Soviets, Covert Operations, Firestorm, Aftermath, Gallery, Counterstrike" />
    <meta name="author" content="CnCNet">
    <meta name="google-site-verification" content="UACqC83TaSFSDZsv31UMLMgzDKasIAdB7IEGP9IUSEM" />
    <meta property="og:title" content="@yield('title') - CnCNet" />
    <meta property="og:image" content="/images/meta.png" />
    <meta property="og:url" content="{{ Request::url() }}" />
    <meta property="og:type" content="website" />

    <link rel="stylesheet" href="/css/app.css?v=2.1.11" />
    <link rel="apple-touch-icon" sizes="152x152" href="/images/meta/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/meta/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/meta/favicon-16x16.png">
    <link rel="mask-icon" href="/images/meta/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    <meta name="color-scheme" content="dark">
    <title>@yield('title') - CnCNet</title>
    @yield('meta')
    @yield('css')
    @yield('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500" rel="stylesheet">
    <script src="/js/lottie.js"></script>
    <script src="/js/popper.js"></script>
    <script src="/js/tippy.js"></script>

    @if(\Carbon\Carbon::now()->month == 12)
        @include("components.snow")
    @endif
</head>

<body class="@yield('body-class'){{ $bodyClass }}">
    <div class="support-cncnet">
        <p class="lead">CnCNet relies on your support ❤️</p>
        <div class="support-cta">
            <a href="https://opencollective.com/cncnet" class="btn btn-primary" target="_blank" rel="nofollow">Support CnCNet via Open Collective</a>
        </div>
    </div>

    @if(isset($history))
        @include('components.countdown', ['target' => $history->ends->toISO8601String()])
    @endif


    <div class="page-feature">
        @include('components.navigation.navbar')

        @hasSection('feature-image')
            <div class="page-image-feature" style="background-image:url(@yield('feature-image'))">
                @yield('feature')
            </div>
        @endif

        @hasSection('feature-video')
            <div class="page-video-feature">
                <div class="video" style="background-image:url( @yield('feature-video-poster') )">
                    <video autoplay="true" loop="" muted="" preload="none" src="@yield('feature-video')">
                    </video>
                </div>
                @yield('feature')
            </div>
        @endif
        <div id="tsparticles"></div>
    </div>

    <main class="main">
        @yield('content')
        @yield('breadcrumb')
    </main>

    @include('components.footer')
    @yield('footer')

    <script src="/js/vendor/bootstrap.js"></script>
    <script src="/js/vendor/jquery.js"></script>
    <script src="/js/cncnet-online.js" defer></script>
    <script src="/js/cncnet-countdown.js" defer></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="popover"]')
            .forEach(popover => {
                new bootstrap.Popover(popover)
            });
    </script>
    @yield('js')
    @yield('scripts')
    <script>
        window.addEventListener("load", (event) => {
            document.body.classList.add("loaded");
        });
    </script>
</body>

</html>
