@extends('layouts.app')
@section('title', 'Ladder')
{{-- @section('feature-image', '/images/feature/feature-index.jpg') --}}
@section('feature-video', '//cdn.jsdelivr.net/gh/cnc-community/files@1.4/red-alert-2.mp4')

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Rankings</span>
                    </h1>

                    <p class="lead">
                        Compete against players all over the world in our <strong>1vs1</strong> ranked ladders
                    </p>

                    @if (!\Auth::user())
                        <div class="mt-4">
                            <a class="btn btn--outline-primary me-2 btn-animated btn-animated-center btn-size-lg" href="/auth/login">Register</a>
                            <a class="btn btn--outline-secondary btn-animated btn-animated-center btn-size-lg" href="/auth/login">Login</a>
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
                <div class="row">
                    @foreach ($ladders as $history)
                        <div class="col-12 col-lg-4">
                            <a href="/ladder/{{ $history->short . '/' . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="ladder-link">
                                <div class="ladder-cover cover-{{ $history->ladder->abbreviation }} text-center"
                                    style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover.png' }}')">
                                    <h1>{{ $history->ladder->name }}</h1>
                                    <p class="m-0">
                                        <strong>1vs1 Ranked Match</strong><br />
                                        {{ Carbon\Carbon::parse($history->starts)->format('F Y') }}
                                    </p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
