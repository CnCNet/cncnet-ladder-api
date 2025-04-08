@extends('layouts.app')
@section('title', 'Ladder Login')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Register</x-slot>
        <x-slot name="description">
            Register an account for the CnCNet Ladders
        </x-slot>
        @if (!\Auth::user())
            <div class="hero-btn-group">
                <a class="btn btn-outline-secondary me-3 btn-lg" href="/auth/login">Login</a>
            </div>
        @endif
    </x-hero-with-video>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        Register
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-8 m-auto">
                <h3>How to play</h3>

                <ol class="list-styled">
                    <li>
                        <p>Create your Ladder Account below.</p>
                    </li>
                    <li>
                        <p>Once complete, create a Username to play online with.</p>
                    </li>
                    <li>
                        <p>Login to the CnCNet Ranked Match client with your Ladder Account.</p>
                    </li>
                    <li>
                        <p>Click "Ranked Match" and wait to play ladder games!</p>
                    </li>
                </ol>

                <p>
                    If you have trouble registering please contact us on our <a href="https://cncnet.org/discord" class="text-underline">Discord.</a>
                </p>

                @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> There were some problems with your input.<br><br>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="signUpForm" class="form-horizontal mt-5" role="form" method="POST" action="/auth/register">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    <div class="form-floating mb-3">
                        <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}">
                        <label for="name">Name</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input id="emailAddress" type="email" class="form-control" name="email" value="{{ old('email') }}">
                        <label for="emailAddress">Email address</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input id="password" type="password" class="form-control" name="password" value="{{ old('password') }}">
                        <label for="password">Password</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation"
                            value="{{ old('password_confirmation') }}">
                        <label for="password_confirmation">Password (confirmed)</label>
                    </div>

                    <input type="hidden" name="play_nay" />

                    <div class="form-group">
                        <p>
                            By registering and using the Ranked Match software and related CnCNet services, you agree to the CnCNet <a
                                href="https://cncnet.org/terms-and-conditions" target="_blank" class="text-underline">Terms &amp; Conditions</a>
                            and to our <a href="https://cncnet.org/community-guidelines-and-rules" class="text-underline">CnCNet Community Guidelines
                                and Rules</a>.
                        </p>

                        <button type="submit" class="btn btn-primary mt-2">
                            Register
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
