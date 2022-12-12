@extends('layouts.app')
@section('title', 'Ladder Sign up')
@section('feature-image', '/images/feature/feature-td.jpg')

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">Sign up</strong>
                        <br />
                        <span>Ladder Rankings</span>
                    </h1>

                    <p class="lead text-uppercase">
                        <strong>1 vs 1 Ranked Match</strong></small>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="mt-5 pt-4">
        <div class="container">
            <div class="row">
                <div class="col-8 m-auto">
                    <h2 class="mb-2 pb-2">Create your CnCNet Ladder Account</h2>

                    <h4>How to play</h4>

                    <ol>
                        <li>Create your Ladder Account below.</li>
                        <li>Once complete, create a Nickname to play online with.</li>
                        <li>Login to the CnCNet Quick Match client with your Ladder Account.</li>
                        <li>Click Quick Match and wait to play ladder games!</li>
                    </ol>


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

                    <form id="signUpForm" class="form-horizontal" role="form" method="POST" action="/auth/register">
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
                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" value="{{ old('password_confirmation') }}">
                            <label for="password_confirmation">Password (confirmed)</label>
                        </div>

                        <input type="hidden" name="play_nay" />

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <p class="mb-2">
                                    <small>
                                        By registering and using the Quick Match software and related sites,
                                        you agree to the CnCNet <a href="https://cncnet.org/terms-and-conditions" target="_blank">Terms &amp; Conditions</a>
                                    </small>
                                </p>
                                <button type="submit" class="btn btn-primary">
                                    Register
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        </div>
    @endsection
