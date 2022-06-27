<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navigation-links"
                aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="/"><img src="/images/logo.png" alt="CnCNet Logo" /></a>
        </div>
        <div class="collapse navbar-collapse" id="navigation-links">
            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                        aria-expanded="false">Downloads <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li role="separator" class="nav-title">C&amp;C Originals</li>
                        <li><a href="//cncnet.org/command-and-conquer">Command &amp; Conquer</a></li>
                        <li><a href="//cncnet.org/red-alert">Red Alert</a></li>
                        <li><a href="//cncnet.org/dune-2000">Dune 2000</a></li>
                        <li><a href="//cncnet.org/tiberian-sun">Tiberian Sun</a></li>
                        <li><a href="//cncnet.org/red-alert-2">Red Alert 2</a></li>
                        <li><a href="//cncnet.org/yuris-revenge">Yuri's Revenge</a></li>
                        <li><a href="//cncnet.org/renegade">Renegade</a></li>
                        <li role="separator" class="divider"></li>
                        <li role="separator" class="nav-title">C&amp;C Mods</li>
                        <li><a href="//cncnet.org/dawn-of-the-tiberium-age">Dawn of the Tiberium Age</a></li>
                        <li><a href="//cncnet.org/mental-omega">Mental Omega</a></li>
                        <li><a href="//cncnet.org/twisted-insurrection">Twisted Insurrection</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="//cncnet.org/download">View All Downloads</a></li>
                    </ul>
                </li>
                <li><a href="//forums.cncnet.org">Forums</a></li>

                @if (isset($ladders))
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">Ladders <span class="caret"></span></a>
                        <ul class="dropdown-menu" style="min-width:250px">
                            <li role="separator" class="nav-title">C&amp;C Live Ladders</li>
                            @foreach ($ladders as $history)
                                <li>
                                    <a href="/ladder/{{ $history->short . '/' . $history->ladder->abbreviation }}/"
                                        title="{{ $history->ladder->name }}">
                                        {{ $history->ladder->name }}
                                    </a>
                                </li>
                            @endforeach

                            <li role="separator" class="divider"></li>
                            <li role="separator" class="nav-title">C&amp;C Ladder Champions</li>

                            @foreach ($ladders as $history)
                                <li>
                                    <a href="/ladder-champions/{{ $history->abbreviation }}/"
                                        title="{{ $history->ladder->name }}">
                                        {{ $history->ladder->name }} Winners
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif

                @if (isset($clan_ladders) && $clan_ladders->count() > 0)
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">Clans <span class="caret"></span></a>
                        <ul class="dropdown-menu" style="min-width:250px">
                            <li role="separator" class="nav-title">C&amp;C Live Ladders</li>
                            @foreach ($clan_ladders as $history)
                                <li>
                                    <a href="/clans/{{ $history->ladder->abbreviation . '/leaderboards/' . $history->short }}/"
                                        title="{{ $history->ladder->name }}">
                                        {{ $history->ladder->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
                @if (isset($private_ladders) && $private_ladders->count() > 0)
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">Private <span class="caret"></span></a>
                        <ul class="dropdown-menu" style="min-width:250px">
                            <li role="separator" class="nav-title">C&amp;C Live Ladders</li>
                            @foreach ($private_ladders as $ladder)
                                <li>
                                    <a href="{{ $ladder->latestLeaderboardUrl() }}" title="{{ $ladder->name }}">
                                        {{ $ladder->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                        aria-expanded="false">Community <span class="caret"></span></a>
                    <ul class="dropdown-menu" style="min-width:200px">
                        <li role="separator" class="nav-title">CnCNet Community</li>
                        <li><a href="//cncnet.org/community">News</a></li>
                        <li><a href="//cncnet.org/developers">Developers</a></li>
                        <li><a href="//facebook.com/cncnet" target="_blank">Facebook</a></li>
                        <li><a href="//twitter.com/cncnetofficial" target="_blank">Twitter</a></li>
                        <li><a href="//www.youtube.com/user/CnCNetOfficial?sub_confirmation=1"
                                target="_blank">YouTube</a></li>
                        <li><a href="//discord.gg/aJRJFe5" target="_blank">Discord</a></li>
                        <li role="separator" class="divider"></li>
                        <li role="separator" class="nav-title">C&amp;C Community</li>
                        <li><a href="//cncnz.com" rel="nofollow" target="_blank">CNCNZ.com <small>(C&amp;C
                                    News)</small></a></li>
                        <li><a href="//ppmforums.com/" rel="nofollow" target="_blank">Project Perfect Mod
                                <small>(C&amp;C Modding)</small></a></li>
                        <li><a href="//cnc-comm.com/" rel="nofollow" target="_blank">CNC-COMM <small>(C&amp;C
                                    Nostalgia)</small></a></li>
                        <li role="separator" class="divider"></li>
                        <li role="separator" class="nav-title">C&amp;C Generals and beyond</li>
                        <li><a href="//cnc-online.net" rel="nofollow" target="_blank">C&amp;C:Online <small>(Gamespy
                                    Replacement)</small></a></li>
                    </ul>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="status" class="dropdown-toggle status-cncnet status-text" data-toggle="dropdown"
                        role="button" aria-haspopup="true" aria-expanded="false">Players Online <span
                            class="online">...</span> <span class="caret"></span></a>
                    <ul class="dropdown-menu status-games">
                        <li class="game-status status-td"><a href="//cncnet.org/command-and-conquer">Command &amp;
                                Conquer <span class="online">999</span></a></li>
                        <li class="game-status status-ra"><a href="//cncnet.org/red-alert">Red Alert <span
                                    class="online">999</span></a></li>
                        <li class="game-status status-d2"><a href="//cncnet.org/dune-2000">Dune 2000 <span
                                    class="online">999</span></a></li>
                        <li class="game-status status-ts"><a href="//cncnet.org/tiberian-sun">Tiberian Sun <span
                                    class="online">999</span></a></li>
                        <li class="game-status status-yr"><a href="//cncnet.org/yuris-revenge">Yuri's Revenge <span
                                    class="online">999</span></a></li>
                        <li class="game-status status-rg"><a href="//cncnet.org/renegade">Renegade <span
                                    class="online">...</span></a></li>
                        <li role="seperator" class="divider"></li>
                        <li class="game-status status-dta"><a href="//cncnet.org/dawn-of-the-tiberium-age">Dawn of the
                                Tiberium Age <span class="online">999</span></a></li>
                        <li class="game-status status-mo"><a href="//cncnet.org/mental-omega">Mental Omega<span
                                    class="online">999</span></a></li>
                        <li class="game-status status-ti"><a href="//cncnet.org/twisted-insurrection">Twisted
                                Insurrection<span class="online">999</span></a></li>
                        <li role="seperator" class="divider"></li>
                        <li class="cncnet-status"><a href="//cncnet.org/status">CnCNet Status <span
                                    class="pull-right">OK</span></a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <?php $user = \Auth::user(); ?>

                    <a href="status" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                        aria-expanded="false">
                        <div class="user-profile">
                            <ul class="list-inline">
                                <li>
                                    <i class="fa fa-user-circle fa-lg" aria-hidden="true"></i>
                                </li>
                                <li>
                                    @if (isset($user))
                                        {{ $user->name }} <span class="caret"></span>
                                    @else
                                        Ladder Account <span class="caret"></span>
                                    @endif
                                </li>
                        </div>
                    </a>

                    @if (isset($user))
                        <ul class="dropdown-menu">
                            @if ($user->canEditAnyLadders())
                                <li>
                                    <a href="/admin/">Admin</a>
                                </li>
                            @endif
                            <li><a href="/account">Manage Account</a></li>
                            <li>
                            <form method="POST" action="/logout">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <!-- todo -->
                                <button type="submit" class="btn btn-primary" style="background:transparent;">Logout</button>
                            </form>
                        </ul>
                    @else
                        <ul class="dropdown-menu">
                            <li><a href="/login">Sign in</a></li>
                            <li><a href="/register">Sign up</a></li>
                        </ul>
                    @endif
                </li>
            </ul>
        </div>
    </div>
</nav>
