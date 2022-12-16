@extends('layouts.app')
@section('title', 'CnCNet Help Guides')

@section('cover')
    /images/feature/feature-index.jpg
@endsection

@section('feature')
    <div class="feature-background sub-feature-background">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-8 col-md-offset-2">
                    <h1>
                        CnCNet <strong>Help Guides</strong>
                    </h1>
                    <p>
                        OBS Stream Profile Ranked Match Stats
                    </p>
                </div>
            </div>
        </div>
    </div>
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

    <section class="light-texture game-detail">
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

        </div>
    </section>
@endsection
