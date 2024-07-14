@php $bodyClass = ""; @endphp
@hasSection('body-feature-image')
@php $bodyClass = "body-feature-image"; @endphp
@endif

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - CnCNet</title>

    <meta property="og:title" content="@yield('title') - CnCNet" />
    <meta property="og:image" content="/images/meta.png" />
    <meta property="og:url" content="{{ Request::url() }}" />
    <meta property="og:type" content="website" />
    <meta name="keywords"
        content="C&amp;C, Command and Conquer, C&amp;C95, C&quot;C1, RA, RA95, CnCNet, Online, C&amp;C95 Online,
        C&amp;C GLE, LANmate, Tiberian, Sun, Tiberium, Red Alert, Red, Alert, Kane, Stalin, Classics, Forums, website, online,
        chat, GDI, Nod, The Brotherhood of Nod, The Global Defense Initiative, Allies, Soviets, Covert Operations, Firestorm, Aftermath, Gallery, Counterstrike" />

    <meta name="author" content="CnCNet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Color+Emoji&family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#2b2b2b">
    <meta name="msapplication-TileColor" content="#2b2b2b">
    <meta name="theme-color" content="#2b2b2b">
    <meta name="color-scheme" content="dark">
   
    @vite(['resources/stylesheets/app.scss','resources/typescript/App.ts'])

    @yield('meta')
    @yield('css')
    @yield('head')

    <script src="/js/lottie.js"></script>
    <script src="/js/popper.js"></script>
    <script src="/js/tippy.js"></script>
    <script src="/js/vendor/jquery.js"></script>
    
</head>

<body class="@yield('body-class'){{ $bodyClass }} @hasSection('page-body-class')@yield('page-body-class')@endif">

    @if(isset($history))
        @include('components.countdown', ['target' => $history->ends->toISO8601String()])
    @endif

    <a href="#" class="live-event js-live-event hidden" target="_blank" title="WATCH LIVE - RED ALERT 2 &amp; YURI'S REVENGE WORLD CHAMPIONSHIPS">
        WATCH LIVE - RED ALERT 2 &amp; YURI'S REVENGE WORLD CHAMPIONSHIPS
    </a>
    
    @include('components.navigation.navbar')

    @yield('feature')

    <main class="main">
        <div class="support-cncnet">
            <p class="lead">Love playing C&C online? CnCNet relies on donations to keep running.</p>
            <div class="support-cta">
                <a href="https://opencollective.com/cncnet" class="btn btn-outline-primary" target="_blank" rel="nofollow">Donate via Open Collective</a>
            </div>
        </div>

        @yield('content')
        @yield('breadcrumb')
    </main>

    @include('components.navigation.fullscreen-menu')
    @include('components.footer')

    @yield('footer')

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
    <script>
        // Function to fetch data from the API and check if the "live" response is true or false
        async function checkIfEventIsLive() {
            const apiUrl = '/api/v2/events'; // Replace with your actual API URL

            try {
                const response = await fetch(apiUrl);
                
                // Check if the response is OK (status code 200-299)
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Parse the response as JSON
                const data = await response.json();
                let liveBanner = document.querySelector(".js-live-event");
                
                // Check if the "live" field is true or false
                if (data.live === true) {
                    liveBanner.classList.remove("hidden");
                    liveBanner.href = data.url;
                } else {
                    liveBanner.classList.add("hidden");
                }
            } catch (error) {
                console.error("There was an error fetching the data:", error);
            }
        }

        // Call the function
        checkIfEventIsLive();
    </script>
</body>

</html>
