@extends('layouts.app')
@section('title', 'Ladder')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3">
                        <strong class="fw-bold">404</strong>
                        <span>Kane Not Found</span>
                    </h1>

                    <ul class="list-inline">
                        <li>
                            <a class="btn btn-primary" href="/" role="button" title="Play Now">Back to Home</a>
                        </li>
                    </ul>
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
