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
                    <p class="lead">
                        Below are the Ranked Ladders aavailable to play on. Click into a game to manage your ladder account, <br />
                        including creating usernames and managing clans.
                    </p>
                </div>
            </div>
            <div class="mini-breadcrumb d-none d-lg-flex">
                <div class="mini-breadcrumb-item">
                    <a href="/account">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        All Ladder Accounts
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
                    <div class="mt-4 mb-4">
                        <h2 class="text-center mb-5 mt-5 "><strong>Verify Your Email Address Before You Can Play!</strong></h2>
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

            <div class="mt-4 mb-4 pt-4">
                <h3>
                    <span class="material-symbols-outlined icon pe-2">
                        settings
                    </span>
                    <strong>Account</strong> Settings
                </h3>
            </div>

            <p class="lead">Manage all ladder account settings, including Ladder Avatar, Social links and Points filter.</p>

            @if (isset($userSettings))
                <a href="account/settings" class="btn btn-outline btn-size-md">All Ladder Account Settings</a>
            @endif
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
                            <strong>1vs1</strong> Player Ladders
                        </h2>

                        <p class="lead col-md-8 mb-4">
                            For new Red Alert 2 or Yuri's Revenge players joining for the first time, consider playing in the "YR Blitz 1vs1 Ladder" to
                            gain experience before playing Red Alert 2 & Yuri's Revenge Ladder matches.
                        </p>

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
                        <h2 class="mb-4 mt-4">
                            <span class="material-symbols-outlined icon">
                                military_tech
                            </span>
                            <strong>2vs2 Clan</strong> Ladders
                        </h2>
                        <div class="d-flex flex-wrap">
                            @foreach ($clan_ladders as $history)
                                @include('components.ladder-box', [
                                    'history' => $history,
                                    'url' => \App\URLHelper::getAccountLadderUrl($history),
                                    'abbrev' => $history->ladder->abbrev,
                                ])
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
                </div>
            </div>
        </div>
    </section>
@endsection
