<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="C&amp;C, Command and Conquer, C&amp;C95, C&quot;C1, RA, RA95, CnCNet, Online, C&amp;C95 Online, 
        C&amp;C GLE, LANmate, Tiberian, Sun, Tiberium, Red Alert, Red, Alert, Kane, Stalin, Classics, Forums, website, online, 
        chat, GDI, Nod, The Brotherhood of Nod, The Global Defense Initiative, Allies, Soviets, Covert Operations, Firestorm, Aftermath, Gallery, Counterstrike" />
    <meta name="author" content="CnCNet">
    <meta name="google-site-verification" content="UACqC83TaSFSDZsv31UMLMgzDKasIAdB7IEGP9IUSEM"/>
    <meta property="og:title" content="@yield('title') - CnCNet" />
    <meta property="og:image" content="https://cncnet.org/images/meta.png" />
    <meta property="og:url" content="{{ Request::url() }}"/>
    <meta property="og:type" content="website" />
    @yield('meta')
    <title>@yield('title') - CnCNet</title>
    @yield('css')
    <link rel="stylesheet" href="/css/app.css" />
    <link rel="stylesheet" href="/css/ranks.css" />
    <link rel="stylesheet" href="/css/ladder.css?v=0.0.6" />
    <link rel="stylesheet" href="/css/flags.css?v=0.0.1" />
    <link rel="stylesheet" href="/css/font-awesome.min.css" />
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="images/favicon/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="images/favicon/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="images/favicon/manifest.json">
    <link rel="mask-icon" href="images/favicon/safari-pinned-tab.svg" color="#6b6b6b">
    <meta name="theme-color" content="#ffffff">

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <div class="navigation-component">
        @include('components.navigation')
    </div>

    <div class="feature-component @yield('video')" style="background-image: url(@yield('cover'));">
        @yield('feature')
    </div>

    <div class="content-component">
        @yield('content')
    </div>

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
                        <li><a href="developers" title="{{ trans('footer.footer_irc') }}">{{ trans('footer.footer_irc') }}</a></li>
                        <li><a href="https://discord.gg/aJRJFe5" title="{{ trans('footer.footer_discord') }}" target="_blank">{{ trans('footer.footer_discord') }}</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h3>{{ trans('footer.footer_donations') }}</h3>
                    <p>{{ trans('footer.footer_donations_description') }}</p>
                    <a class="btn btn-tertiary btn-md" href="https://www.paypal.com/ua/cgi-bin/webscr?cmd=_flow&SESSION=I7908uFEO96aok1UVLzKOmG8oxfW4PD2BKYpI67VyQyVniDU9lvxJk2AC3K&dispatch=5885d80a13c0db1f8e263663d3faee8d94717bd303200c3af9aadd01a5f55080" target="_blank">
                        {{ trans('footer.footer_donations_cta_primary') }}
                    </a>
                </div>

                <div class="col-md-4">
                    <h3>{{ trans('footer.footer_support_us') }}</h3>
                    <p>{{ trans('footer.footer_support_us_description') }}</p>
                    
                    <ul class="list-inline">
                        <li><a href="http://facebook.com/cncnet" title="Follow CnCNet on Facebook" target="_blank"><i class="fa fa-facebook fa-2x fa-fw"></i></a></li>
                        <li><a href="http://twitter.com/cncnetofficial" title="Follow CnCNet on Twitter" target="_blank"><i class="fa fa-twitter fa-2x fa-fw"></i></a></li>
                        <li><a href="https://www.youtube.com/user/CnCNetOfficial?sub_confirmation=1" title="Subscribe to CnCNet on YouTube" target="_blank"><i class="fa fa-youtube fa-2x fa-fw"></i></a></li>
                        <li><a href="http://google.com/+CncnetOrgOfficial" title="Add CnCNet to your Circles" target="_blank"><i class="fa fa-google-plus fa-2x fa-fw"></i></a></li>
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
                        <li>Copyright  &copy; CnCNet 2009 - {{ Date("Y") }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="/js/cncnet-online.js"></script>

    @yield('js')
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', 'UA-19628724-6']);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
</body>
</html>