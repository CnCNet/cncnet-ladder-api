@extends('layouts.app')
@section('title', 'Account')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet Ladder Account
                </h1>
                <p class="text-uppercase">
                    Play. Compete. <strong>Conquer.</strong>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section>
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center" style="padding-bottom: 40px;">
                        <h1>Hi {{ $user->name }}
                            <button class="btn btn-link inline-after-edit" data-toggle="modal" data-target="#renameUser">
                                <span class="fa fa-edit"></span>
                            </button>
                        </h1>
                        <p class="lead">Manage everything to do with your CnCNet Ladder Account here.</p>

                        @if(isset($userSettings))
                            <a href="account/settings" class="btn btn-primary">User Settings</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
    .tutorial {
        padding: 15px;
        color: white;
    }
</style>
<section class="cncnet-features dark-texture">
    <div class="container">

        <div class="row">
            @if(!$user->email_verified)
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
                @include("components.form-messages")
            </div>
        </div>

        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <h2>1v1 Ladders</h2>
                </div>
                @foreach($ladders as $history)
                <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                    <a href="/account/{{ $history->ladder->abbreviation }}/list" title="{{ $history->ladder->name }}" class="ladder-link">
                        <div class="ladder-cover cover-{{ $history->ladder->abbreviation}}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . "-cover.png" }}')">
                            <div class="details">
                                <div class="type">
                                    <h1>{{ $history->ladder->name }}</h1>
                                    <p class="lead">1<strong>vs</strong>1</p>
                                </div>
                            </div>
                            <div class="badge-cover">
                                <ul class="list-inline">
                                    <li>
                                        <p>{{ Carbon\Carbon::parse($history->starts)->format('F Y') }} Competition</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
                @foreach($private_ladders as $history)
                <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                    <a href="/account/{{ $history->ladder->abbreviation }}/list" title="{{ $history->ladder->name }}" class="ladder-link">
                        <div class="ladder-cover cover-{{ $history->ladder->abbreviation}}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . "-cover.png" }}')">
                            <div class="details">
                                <div class="type">
                                    <h1>{{ $history->ladder->name }}</h1>
                                    <p class="lead">1<strong>vs</strong>1</p>
                                </div>
                            </div>
                            <div class="badge-cover">
                                <ul class="list-inline">
                                    <li>
                                        <p>{{ Carbon\Carbon::parse($history->starts)->format('F Y') }} Competition</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            <div class="row">
                @if($clan_ladders->count() > 0)
                <div class="col-md-12">
                    <h2>Clan Ladders</h2>
                </div>
                @endif
                @foreach($clan_ladders as $history)
                <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                    <a href="/account/{{ $history->ladder->abbreviation }}/list" title="{{ $history->ladder->name }}" class="ladder-link">
                        <div class="ladder-cover cover-{{ $history->ladder->abbreviation}}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . "-cover.png" }}')">
                            <div class="details">
                                <div class="type">
                                    <h1>{{ $history->ladder->name }}</h1>
                                    <p class="lead">1<strong>vs</strong>1</p>
                                </div>
                            </div>
                            <div class="badge-cover">
                                <ul class="list-inline">
                                    <li>
                                        <p>{{ Carbon\Carbon::parse($history->starts)->format('F Y') }} Competition</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
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