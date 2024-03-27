@extends('layouts.app')
@section('title', 'Account')
@section('body-class', 'ladder-account')

@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Account</span>
                    </h1>
                    <p class="lead">
                        Below are all the Ranked Ladders. Click into a ladder to manage your usernames and clans.
                    </p>
                </div>
            </div>
            <div class="mini-breadcrumb d-none d-lg-flex">
                <div class="mini-breadcrumb-item">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </div>
                <div class="mini-breadcrumb-item">
                    <a href="/account">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        Manage Ladder Account
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
                        Manage Ladder Account
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section>
        <div class="container">
            <div class="row">
                @if (!$user->email_verified)
                    <div class="mt-4 mb-4">
                        <h2 class="text-center mb-5 mt-5 "><strong>Verify Your Email Address Before You Can
                                Play!</strong></h2>
                        <div class="text-center">
                            <form class="form" method="POST" name="verify" action="/account/verify">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button type="submit" class="btn btn-primary">Click Here to Send a New Email</a>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            @include('auth.account-settings-nav')
        </div>

        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    @include('components.form-messages')
                </div>
            </div>

            <div class="pt-4">
                <div class="row">
                    <div class="mt-2 mb-2">

                        <h2 class="mb-4 mt-4">
                            <span class="material-symbols-outlined icon">
                                military_tech
                            </span>
                            <strong>New to Ranked Match?</strong>

                        </h2>
                        <p class="lead">
                            Pick the ladder you want to play on below and follow the "how to play" instructions.
                        </p>

                        <h2 class="mb-4 mt-4">
                            <span class="material-symbols-outlined icon">
                                military_tech
                            </span>
                            <strong>1vs1</strong> Player Ladders
                        </h2>

                        <div class="d-flex flex-wrap mini-game-box-ladder-links">
                            @foreach ($ladders as $history)
                                @include('components.ladder-box', [
                                    'history' => $history,
                                    'url' => \App\Models\URLHelper::getAccountLadderUrl($history),
                                ])
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-2 mb-2">
                        <h2 class="mb-4 mt-4">
                            <span class="material-symbols-outlined icon">
                                military_tech
                            </span>
                            <strong>2vs2 Clan</strong> Ladders
                        </h2>
                        <div class="d-flex flex-wrap mini-game-box-ladder-links">
                            @foreach ($clan_ladders as $history)
                                @if (!$history->ladder->private)
                                    @include('components.ladder-box', [
                                        'history' => $history,
                                        'url' => \App\Models\URLHelper::getAccountLadderUrl($history),
                                        'abbrev' => $history->ladder->abbrev,
                                    ])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-2 mb-2">
                        <h2 class="mb-4 mt-4">
                            <span class="material-symbols-outlined icon">
                                military_tech
                            </span>
                            <strong>Private</strong> Ladders
                        </h2>
                        <div class="d-flex flex-wrap mini-game-box-ladder-links">
                            @foreach ($private_ladders as $history)
                                @include('components.ladder-box', [
                                    'history' => $history,
                                    'url' => \App\Models\URLHelper::getAccountLadderUrl($history),
                                    'abbrev' => $history->ladder->game,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
