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
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Styleguide</span>
                    </h1>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <h3>Stats - YR</h3>
    <ul>
        <li>Start: {{ $start }}</li>
        <li>End: {{ $end }}</li>
        <li>Count: {{ $matchCount }}</li>
    </ul>
@endsection
