@extends('layouts.app')
@section('title', 'Account')
@section('body-class', 'ladder-account')

@section('feature-image', '/images/feature/feature-index.jpg')

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
                        <a href="account/settings" class="btn btn-outline">User Settings</a>
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
                    @foreach ($ladders as $history)
                        <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                            <a href="/account/{{ $history->ladder->abbreviation }}/list" title="{{ $history->ladder->name }}" class="ladder-link">
                                <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover.png' }}')">
                                    <h1>{{ $history->ladder->name }}</h1>
                                    <p class="m-0">
                                        <strong>1vs1 Ranked Match</strong><br />
                                        {{ Carbon\Carbon::parse($history->starts)->format('F Y') }}
                                    </p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                    @foreach ($private_ladders as $history)
                        <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                            <a href="/account/{{ $history->ladder->abbreviation }}/list" title="{{ $history->ladder->name }}" class="ladder-link">
                                <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover.png' }}')">
                                    <h1>{{ $history->ladder->name }}</h1>
                                    <p class="m-0">
                                        <strong>Private Ladder</strong><br />
                                        {{ Carbon\Carbon::parse($history->starts)->format('F Y') }}
                                    </p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
                <div class="row">
                    @if ($clan_ladders->count() > 0)
                        <div class="col-md-12">
                            <h2>Clan Ladders</h2>
                        </div>
                    @endif
                    @foreach ($clan_ladders as $history)
                        <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                            <a href="/account/{{ $history->ladder->abbreviation }}/list" title="{{ $history->ladder->name }}" class="ladder-link">
                                <h1>{{ $history->ladder->name }}</h1>
                                <p class="m-0">
                                    <strong>Clan Ladder</strong><br />
                                    {{ Carbon\Carbon::parse($history->starts)->format('F Y') }}
                                </p>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="renameUser" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Change Your Username</h3>
                </div>
                <div class="modal-body clearfix">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                                <div class="account-box">
                                    <form method="POST" action="/account/rename">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="id" value="{{ $user->id }}">

                                        <div class="form-group">
                                            <label for="name">Username</label>
                                            <input type="text" name="name" class="form-control" id="name" placeholder="New Username" value="{{ $user->name }}">
                                        </div>

                                        <button type="submit" class="btn btn-primary">Change</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
