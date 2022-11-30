@extends('layouts.app')
@section('title', 'Account')
@section('body-class', 'ladder-account')

@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Account</span>
                    </h1>

                    <p class="lead">Manage everything to do with your CnCNet Ladder Account here.</p>

                    @if (isset($userSettings))
                        <a href="account/settings" class="btn btn-outline btn-size-lg">User Settings</a>
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
                        {{ $user->name }}'s account
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
                    <div class="col-md-12 tutorial">
                        <h2 class="text-center"><strong>Verify Your Email Address Before You Can Play!</strong></h2>
                        <div class="text-center">
                            <form class="form" method="POST" name="verify" action="/account/verify">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button type="submit" class="btn btn-link">Click Here to Send a New Code</a>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="row">
                <div class="col-md-12">
                    @include('components.form-messages')
                </div>
            </div>

            <div class="pt-4">
                <div class="row">
                    <div class="mt-2 mb-2">
                        <h3>
                            <span class="material-symbols-outlined icon">
                                military_tech
                            </span>
                            <strong>1vs1</strong> Ladders
                        </h3>

                        <div class="d-flex flex-wrap">
                            @foreach ($ladders as $history)
                                @include('components.ladder-box', [
                                    'history' => $history,
                                    'url' => \App\URLHelper::getAccountLadderUrl($history),
                                ])
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-2 mb-2">
                        <h3>
                            <span class="material-symbols-outlined icon">
                                military_tech
                            </span>
                            <strong>Private</strong> Ladders
                        </h3>
                        <div class="d-flex flex-wrap">
                            @foreach ($private_ladders as $history)
                                @include('components.ladder-box', [
                                    'history' => $history,
                                    'url' => \App\URLHelper::getAccountLadderUrl($history),
                                    'abbrev' => $history->ladder->game,
                                ])
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-2 mb-2">
                        <h3>
                            <span class="material-symbols-outlined icon">
                                military_tech
                            </span>
                            <strong>Clan</strong> Ladders
                        </h3>
                        <div class="d-flex flex-wrap">
                            @foreach ($clan_ladders as $history)
                                @include('components.ladder-box', [
                                    'history' => $history,
                                    'url' => \App\URLHelper::getAccountLadderUrl($history),
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
