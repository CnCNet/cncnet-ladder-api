@extends('layouts.app')
@section('title', 'Ladder Login')
@section('body-feature-image', '/images/feature/feature-index.jpg')

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Login</span>
                    </h1>
                    @if (!\Auth::user())
                        <div class="mt-4">
                            <a type="submit" class="btn btn-primary px-4 me-sm-3" href="/auth/register">Register</a>
                            <a type="submit" class="btn btn-outline" href="/auth/login">Login</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="pt-4">
        <div class="container">
            <nav aria-label="breadcrumb">
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
            </nav>
        </div>
    </section>

    <section class="pt-4">
        <div class="container">
            <div class="row">
                <div class="col-6 m-auto">

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
                            <input type="email" class="form-control" id="floatingInput" placeholder="name@example.com" name="email" value="{{ old('email') }}">
                            <label for="floatingInput">Email address</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="floatingPassword" placeholder="Password" name="password">
                            <label for="floatingPassword">Password</label>
                        </div>

                        <div class="checkbox mb-3">
                            <label>
                                <input type="checkbox" name="remember" value="remember-me"> Remember me
                            </label>
                        </div>

                        <button class="w-100 btn btn-lg btn-primary" type="submit">Login</button>

                        <hr class="my-4">

                        <p>
                            <a href="/password/email">Forgot your password?</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
