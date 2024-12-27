@extends('layouts.app')
@section('title', 'Ladder Login')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Login</x-slot>
        <x-slot name="description">
            Login to manage your ladder account below.
        </x-slot>

        @if (!\Auth::user())
            <div class="hero-btn-group">
                <a class="btn btn-outline-secondary me-3 btn-lg" href="/auth/register">Register</a>
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
                        Login
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 m-auto">
                    <p class="lead mb-5 mt-5">
                        Login with your email and password below.
                        If you have trouble logging in please contact us on our <a href="https://cncnet.org/discord" class="text-underline">Discord </a>
                        for help.
                    </p>

                    <form class="" method="POST" action="/auth/login">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">

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

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="floatingInput" placeholder="name@example.com" name="email"
                                value="{{ old('email') }}">
                            <label for="floatingInput">Email address</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="floatingPassword" placeholder="Password" name="password">
                            <label for="floatingPassword">Password</label>
                        </div>

                        <div class="checkbox mb-3">
                            <label>
                                <input type="checkbox" name="remember" value="1"> Remember me
                            </label>
                        </div>

                        <button class="w-100 btn btn-primary" type="submit">Login</button>

                        <hr class="my-4">

                        <p>
                            <a href="/auth/password/reset">Forgot your password?</a> or <a href="/auth/register">create
                                an account?</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
