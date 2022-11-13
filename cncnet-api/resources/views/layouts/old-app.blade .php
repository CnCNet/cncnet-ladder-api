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
    <meta property="og:image" content="https://cncnet.org/images/meta.png" />
    <meta property="og:url" content="{{ Request::url() }}" />
    <meta property="og:type" content="website" />

    @yield('meta')
    <title>@yield('title') - CnCNet</title>
    @yield('css')
    @yield('head')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi"
        crossorigin="anonymous">

    <link rel="stylesheet" href="/css/ladder.css" />

    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="/images/favicon/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/images/favicon/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="/images/favicon/manifest.json">
    <link rel="mask-icon" href="/images/favicon/safari-pinned-tab.svg" color="#6b6b6b">
    <meta name="theme-color" content="#ffffff">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500" rel="stylesheet">
    <script src="/js/lottie.js"></script>
</head>

<body class='@yield('body-class')'>
    <section class="navigation">
        @include('components.navigation')
    </section>

    <div class="feature-component @yield('video')" style="background-image: url(@yield('cover'));">
        @yield('feature')
    </div>

    <main class="main">
        @yield('content')
    </main>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-2">
                    <h3>{{ trans('footer.footer_games') }}</h3>
                    <ul class="list-unstyled">
                        <li><a href="//cncnet.org/command-and-conquer">Command &amp; Conquer</a></li>
                        <li><a href="//cncnet.org/red-alert">Red Alert</a></li>
                        <li><a href="//cncnet.org/dune-2000">Dune 2000</a></li>
                        <li><a href="//cncnet.org/tiberian-sun">Tiberian Sun</a></li>
                        <li><a href="//cncnet.org/red-alert-2">Red Alert 2</a></li>
                        <li><a href="//cncnet.org/yuris-revenge">Yuri's Revenge</a></li>
                        <li><a href="//cncnet.org/renegade">Renegade</a></li>
                        <li><a href="//cncnet.org/dawn-of-the-tiberium-age">Dawn of the Tiberium Age</a></li>
                        <li><a href="//cncnet.org/mental-omega">Mental Omega</a></li>
                        <li><a href="//cncnet.org/twisted-insurrection">Twisted Insurrection</a></li>
                    </ul>
                </div>

                <div class="col-md-2">
                    <h3>{{ trans('footer.footer_support') }}</h3>
                    <ul class="list-unstyled">
                        <li><a href="//forums.cncnet.org" title="{{ trans('footer.footer_support') }}">{{ trans('footer.footer_support') }}</a></li>
                        <li><a href="https://cncnet.org/developers" title="Developers">Developers</a></li>
                        <li><a href="https://discord.gg/aJRJFe5" title="{{ trans('footer.footer_discord') }}" target="_blank">{{ trans('footer.footer_discord') }}</a></li>
                    </ul>
                </div>

                <div class="col-md-4">
                    <h3>{{ trans('footer.footer_donations') }}</h3>
                    <p>If you wish to support a specific CnCNet project, please ask in our support forums.</p>
                    <a href="//forums.cncnet.org/forum/11-support/" class="btn btn-tertiary btn-md">Forums</a>
                </div>

                <div class="col-md-4">
                    <h3>{{ trans('footer.footer_support_us') }}</h3>
                    <p>{{ trans('footer.footer_support_us_description') }}</p>

                    <ul class="list-inline">
                        <li><a href="http://facebook.com/cncnet" title="Follow CnCNet on Facebook" target="_blank"><i class="fa fa-facebook fa-2x fa-fw"></i></a></li>
                        <li><a href="http://twitter.com/cncnetofficial" title="Follow CnCNet on Twitter" target="_blank"><i class="fa fa-twitter fa-2x fa-fw"></i></a></li>
                        <li><a href="https://www.youtube.com/user/CnCNetOfficial?sub_confirmation=1" title="Subscribe to CnCNet on YouTube" target="_blank"><i
                                    class="fa fa-youtube fa-2x fa-fw"></i></a></li>
                        <li><a href="https://reddit.com/r/cncnet" title="Subscribe to CnCNet on Reddit" target="_blank"><i class="fa fa-reddit fa-2x fa-fw"></i></a> </li>
                        <li><a href="https://www.twitch.tv/cncnetofficial" title="Subscribe to CnCNet on Twitch" target="_blank"><i class="fa fa-twitch fa-2x fa-fw"></i></a></li>
                        <li><a href="https://github.com/cncnet" title="Follow and Star us on GitHub" target="_blank"><i class="fa fa-github fa-2x fa-fw"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-6">
                    <ul class="list-inline partners text-right">
                        <li>
                            <a href="http://gamesurge.net" target="_blank" title="GameSurge"><img src="/images/gamesurge.png" alt="GameSurge Logo" width="200" /></a>
                        </li>
                        <li><img src="/images/logo.png" width="200" alt="CnCNet Logo" /></li>
                    </ul>
                    <ul class="list-inline text-right">
                        <li><a href="//cncnet.org/privacy-policy" title="{{ trans('footer.footer_privacy_policy') }}">{{ trans('footer.footer_privacy_policy') }}</a></li>
                        <li><a href="//cncnet.org/terms-and-conditions" title="{{ trans('footer.footer_terms_conditions') }}">{{ trans('footer.footer_terms_conditions') }}</a></li>
                        <li>Copyright &copy; CnCNet 2009 - {{ Date('Y') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    @yield('footer')
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous">
    </script>

    <script src="/js/cncnet-online.js"></script>

    @yield('js')
    <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-19628724-6']);
        _gaq.push(['_trackPageview']);

        (function() {
            var ga = document.createElement('script');
            ga.type = 'text/javascript';
            ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(ga, s);
        })();

        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>

</html>
