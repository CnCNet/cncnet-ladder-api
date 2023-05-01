@extends('layouts.app')
@section('title', '500')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">500</strong>
                        <span>Error. Probably mind control.</span>
                    </h1>

                    <ul class="list-inline">
                        <li>
                            <a class="btn btn-primary" href="/" role="button" title="Play Now">Back to Home</a>
                        </li>
                    </ul>

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
    <div class="ladder-index">
        <section class="pt-5 pb-5">
            <div class="container">

            </div>
        </section>
    </div>
@endsection
