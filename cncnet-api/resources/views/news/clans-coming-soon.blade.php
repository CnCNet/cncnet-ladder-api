@extends('layouts.app')
@section('head')
    <script src="/js/chart.min.js"></script>
    <script src="/js/chartjs-adapter-date-fns.bundle.min.js"></script>
@endsection

@section('title', 'News - 2vs2 Clan Ladders')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <span><i class="bi bi-flag-fill icon-clan"></i> <strong>2vs2 Clan Ladders</strong></span>
                    </h1>

                    <p class="lead">
                        Attention, commanders! Brace yourselves, for the clan ladders are here.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('breadcrumb')
@endsection

@section('content')
    <div class="ladder-index">
        <section class="pt-5 pb-5">
            <div class="container">

                <div class="col-md-8 m-auto">
                    <h1 class="mb-4"> 2vs2 Clan Ladder Announcement </h1>
                    <p class="lead">
                        Commanders. Are you sitting down? Red Alert 2 Clan Ladders are now in public beta!
                    </p>

                    <p class="lead">
                        Prepare to showcase your tactical brilliance, coordinate your strategies, outsmart your opponents, and prove your worth as a
                        formidable clan!
                    </p>

                    <h3 class="mt-5">Public Beta</h3>
                    <p class="lead">
                        The public beta for the Clan ladder is now available for Red Alert 2. We will begin a public beta
                        test for Tiberian Sun and Red Alert available in due time.
                    </p>

                    <h3 class="mt-5">Every 🍺 helps!</h3>
                    <p class="lead">
                        If you love what we're doing with the CnCNet Ladders, we'd never say no to a few beers.
                        The developers below are responsible for making the clan ladders happen.
                    </p>

                    <div class="d-flex" style="font-size:19px">
                        <div class="mr-4">
                            <a href="https://www.paypal.com/donate?business=97YLXRUPWZAK8" target="_blank" class="btn btn-outline">
                                <i class="fa bi-paypal fa-lg pe-2"></i>Burg
                            </a>
                        </div>
                        <div class="ps-4 mr-4">
                            <a href="https://www.paypal.com/donate/?business=CWS4JFC2ENMSC" target="_blank" class="btn btn-outline">
                                <i class="fa bi-paypal fa-lg pe-2"></i>xme
                            </a>
                        </div>
                        <div class="ps-4 mr-4">
                            <a href="https://www.paypal.com/donate/?hosted_button_id=CAHPHC3X78KWC"target="_blank" class="btn btn-outline">
                                <i class="fa bi-paypal fa-lg pe-2"></i>neogrant
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
