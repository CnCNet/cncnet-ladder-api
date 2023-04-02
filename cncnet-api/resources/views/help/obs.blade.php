@extends('layouts.app')
@section('title', 'Ladder')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Help Guides</span>
                    </h1>

                    <p>
                        OBS Stream Profile Ranked Match Stats
                    </p>
                    @if (!\Auth::user())
                        <div class="mt-4">
                            <a class="btn btn--outline-primary me-3 btn-size-lg" href="/auth/register">Register</a>
                            <a class="btn btn--outline-secondary btn-size-lg" href="/auth/login">Login</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

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

@section('content')
    <style>
        img {
            max-width: 100%;
        }

        .tutorial-image {
            margin-bottom: 0.5rem;
            max-width: 80%;
        }

        .step {
            margin-bottom: 4rem;
        }
    </style>

    <div class="ladder-index">
        <section class="pt-5 pb-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <h2>How to show Ranked Match Stats in OBS streams</h2>
                    </div>
                </div>

                <div>
                    <div class="step">
                        <h3>Step 1</h3>
                        <p class="lead">
                            Copy the "OBS Stream Profile" for your username found in your ladder account
                            page.
                        </p>
                    </div>

                    <div class="step">
                        <h3>Step 2</h3>
                        <p class="lead">In OBS, create a browser source</p>
                        <div class="tutorial-image">
                            <img src="/images/obs/browser-source.png" alt="Browser source OBS" />
                        </div>
                    </div>

                    <div class="step">
                        <h3>Step 3 </h3>
                        <p class="lead">In the source, paste the URL from the first step. Set the width and height
                            roughly to 635x110
                        </p>
                        <div class="tutorial-image">
                            <img src="/images/obs/browser-source-url.png" alt="Browser source OBS" />
                        </div>
                    </div>

                    <div class="step">
                        <h3>Step 4</h3>
                        <p class="lead">Click OK and resize, place in your stream as required.</p>
                        <div class="tutorial-image">
                            <img src="/images/obs/drag-resize.png" alt="Browser source OBS" />
                        </div>
                    </div>

                    <div class="step">
                        <h3>Step 5</h3>
                        <p class="lead">Save and see the result. The stats will automatically update every 30 seconds.
                        </p>
                        <div class="tutorial-image">
                            <img src="/images/obs/browser-source-result.png" alt="Browser source OBS result" />
                        </div>
                    </div>
                </div>
        </section>
    </div>
@endsection
