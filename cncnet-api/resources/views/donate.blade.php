@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
    /images/feature/feature-index.jpg
@endsection

@section('feature')
    <div class="feature-background sub-feature-background">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-8 col-md-offset-2">
                    <h1>
                        Donate to the <strong>Developers</strong>
                    </h1>
                    <p>
                        Donating to the CnCNet Ladder Development Team
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="dark-texture game-detail supported-games">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <h3>Every donation is appreciated</h3>
                    <p>
                        Below is the incredible team who are responsible for developing the
                        <a href="https://github.com/CnCNet/cncnet-ladder-api">CnCNet Ladder codebase</a>.
                        Each profile will show their own individual Paypal pages you are able to send a donation to.
                    </p>
                    <p>
                        If you choose to donate, firstly, thank you, seriously! All donations are appreciated. Secondly, we
                        will add you
                        to an exclusive private channel on the
                        <a href="https://discord.gg/ZZvg3zEYaa" target="_blank">Yuri's Revenge discord</a>
                        where you'll be able to see new ladder features being proposed and worked on.
                        Once you have donated, please contact an admin of the discord channel to be added.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <section>
        <div class="container">
            <div class="dev-profiles">
                <div class="profile">
                    <h3>
                        <a href="https://github.com/dkeetonx" style="color: white;">
                            <i class="fa fa-github fa-fw"></i>
                            xme
                        </a>
                    </h3>
                    <p>
                        A long time contributor and key figurehead for CnCNet, xme made Quick match
                        on CnCNet possible. Building the QM client along with many of the critical features to make a
                        ladder, xme has maintained various parts of the ladder since development first started in in 2017.
                    </p>
                    <p>
                        Xme has also built the foundations and structure for clan ladders, ready to be built with the next
                        QM client.
                    </p>
                    <p>
                        <a href="https://github.com/CnCNet/cncnet-ladder-api/commits?author=dkeetonx">Over 200+ commits,
                            20,000 additions
                        </a>
                    </p>
                    <p>
                        <a href="https://www.paypal.com/donate/?business=CWS4JFC2ENMSC" style="color: white;"
                            target="_blank" class="btn btn-primary">
                            <i class="fa fa-paypal fa-lg fa-fw"></i>
                            Donate to Xme via PayPal
                        </a>
                    </p>
                </div>

                <div class="profile">
                    <h3>
                        <a href="https://github.com/alexp8" style="color: white;">
                            <i class="fa fa-github fa-fw"></i>
                            Burg
                        </a>
                    </h3>
                    <p>
                        TODO
                    </p>
                    <p>
                        <a href="https://www.paypal.com/donate?business=97YLXRUPWZAK8" style="color: white;" target="_blank"
                            class="btn btn-primary">
                            <i class="fa fa-paypal fa-lg fa-fw"></i>
                            Donate to Burg via PayPal
                        </a>
                    </p>
                </div>

                <div class="profile">
                    <h3>
                        <a href="https://github.com/devo1929" style="color: white;">
                            <i class="fa fa-github fa-fw"></i>
                            devo1929
                        </a>
                    </h3>
                    <div>
                        <p>
                            Devo1929, a contributor towards the
                            <a href="https://github.com/CnCNet/xna-cncnet-client">XNA CnCNet Client</a>, is looking to
                            port over the current QM client to the XNA CnCNet Client codebase, opening up a path for new
                            features and contributors for QM.
                        </p>
                        <p>
                            Developments from Devo1929 in the XNA client are:
                        </p>
                        <ul class="list-styled">
                            <li>
                                <a href="https://github.com/CnCNet/xna-cncnet-client/pull/293" target="_blank">Auto
                                    ally</a>, <a href="https://github.com/CnCNet/xna-cncnet-client/pull/245"
                                    target="_blank">Favorite maps</a>
                            </li>
                            <li><a href="https://github.com/CnCNet/xna-cncnet-client/pull/281" target="_blank">Force random
                                    teams/sides/colors/starts</a></li>
                            <li><a href="https://github.com/CnCNet/xna-cncnet-client/pull/263">Recent players list</a>, <a
                                    href="">PM restrictions/disable</a></li>
                            <li><a href="https://github.com/CnCNet/xna-cncnet-client/search?q=game+filters&type=issues">Game
                                    filters</a>
                            </li>
                            <li>
                                <a
                                    href="https://github.com/CnCNet/cncnet-yr-client-package/commit/83b412f1efb7be420e901c08de1fc8360e08a009">
                                    Automated
                                    build/deploy
                                </a>
                            </li>
                        </ul>
                    </div>
                    <p>
                        <a href="https://www.paypal.com/donate?business=4KDXPWUS99QNS" style="color: white;" target="_blank"
                            class="btn btn-primary">
                            <i class="fa fa-paypal fa-lg fa-fw"></i>
                            Donate to devo1929 via PayPal
                        </a>
                    </p>
                </div>

                <div class="profile">
                    <h3>
                        <a href="https://github.com/grantbartlett" style="color: white;">
                            <i class="fa fa-github fa-fw"></i>
                            neogrant
                        </a>
                    </h3>
                    <p>
                        Neogrant designs and builds anything around the CnCNet websites.
                        Returning in 2022 after a break in 2019, his focus is building new features on the ladder and
                        implementing a new design to CnCNet websites.
                    </p>
                    <p>
                        Developments this year so since returning:
                    <ul>
                        <li>
                            <a href="https://github.com/CnCNet/cncnet-ladder-api/pull/54" target="_blank">Streamers OBS
                                player profile
                                stats</a>
                        </li>
                        <li>
                            <a href="https://github.com/CnCNet/cncnet-ladder-api/pull/91" target="_blank">Upgrading the
                                project</a> and
                            moving to a <a href="https://github.com/CnCNet/cncnet-ladder-api/pull/74" target="_blank">docker
                                environemnt</a>
                        </li>
                        <li>
                            <a href="https://github.com/CnCNet/cncnet-ladder-api/pull/87">Allow private ladders to show up
                                in QM client for testers</a>
                        </li>
                        <li>
                            <a href="https://github.com/CnCNet/cncnet-ladder-api/pull/96" target="_blank">New player
                                win/loss graph stats
                            </a> and <a href="https://github.com/CnCNet/cncnet-ladder-api/pull/98" target="_blank">Map
                                win/loss stats to
                                profile pages.
                        </li>
                    </ul>
                    </p>
                    <p>
                        <a href="https://www.paypal.com/donate/?hosted_button_id=CAHPHC3X78KWC" style="color: white;"
                            target="_blank" class="btn btn-primary">
                            <i class="fa fa-paypal fa-lg fa-fw"></i>
                            Donate to neogrant via PayPal
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
