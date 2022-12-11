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

    <link rel="stylesheet" href="/css/app.css?v=2.0" />
    <link rel="apple-touch-icon" sizes="152x152" href="/images/meta/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/meta/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/meta/favicon-16x16.png">
    <link rel="manifest" href="/images/meta/site.webmanifest">
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
</head>

<body class="@yield('body-class') @hasSection('body-feature-image')
body-feature-image
@endif">
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

    <main class="main">
        @yield('content')
        @yield('breadcrumb')
    </main>

    @include('components.footer')
    @yield('footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous">
    </script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
    <script src="/js/cncnet-online.js"></script>
    @yield('js')

    <script>
        window.addEventListener("load", (event) => {
            document.body.classList.add("loaded");
        });
    </script>
</body>

</html>
