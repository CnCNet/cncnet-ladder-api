@extends('layouts.app')
@section('title', 'Ladder Login')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3">
                        <strong class="fw-bold">Register</strong>
                    </h1>
                    <p class="lead">
                        Register an account for the CnCNet Ladders.
                    </p>
                </div>
            </div>

            <div class="mini-breadcrumb d-none d-lg-flex">
                <div class="mini-breadcrumb-item">
                    <a href="/" class="">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </div>
                <div class="mini-breadcrumb-item">
                    <a href="">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        Register
                    </a>
                </div>
            </div>
        </div>
    </div>
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

                <ol>
                    <li>Create your Ladder Account below.</li>
                    <li>Once complete, create a Username to play online with.</li>
                    <li>Login to the CnCNet Ranked Match client with your Ladder Account.</li>
                    <li>Click "Quick Match" and wait to play ladder games!</li>
                </ol>

                <p>
                    If you have trouble registering <a href="https://cncnet.org/discord">please contact us on our
                        Discord.</a>
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
                        <div class="col-md-6 col-md-offset-4">
                            <p class="mb-2">
                                <small>
                                    By registering and using the Quick Match software and related sites, you agree to
                                    the CnCNet <a href="https://cncnet.org/terms-and-conditions" target="_blank">Terms &amp;
                                        Conditions</a>
                                </small>
                            </p>

                            <button type="submit" class="btn btn-primary mt-2">
                                Register
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
