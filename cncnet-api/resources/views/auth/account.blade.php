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
                        <h1>Hi {{ $user->name }} </h1>
                        <p class="lead">Manage everything to do with your CnCNet Ladder Account here.</p>
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
                @if(!$user->email_verified)<div class="col-md-12 tutorial">
                    <h2 class="text-center"><strong>Verify Your Email Address Before You Can Play!</strong></h2>
                    <div class="text-center">
                        <a href="{{ url("/account/verify") }}">Click Here to Send a New Code</a>
                    </div>
                @endif
            </div>
            <div class="row">
                <div class="col-md-12">
                @include("components.form-messages")
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="account-box">
                        <h2>Add a new username?</h2>
                        <form method="POST" action="account/username">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <p>Usernames will be the name shown when you login to CnCNet clients and play games.</p>
                            
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" name="username" class="form-control" id="username" placeholder="Username">
                            </div>
                            <div class="form-group">
                                <label for="ladder">Ladder</label>
                                <select name="ladder" id="ladder" class="form-control">
                                @foreach($ladders as $history)
                                <option value="{{ $history->ladder->id }}">{{ $history->ladder->name }}</option>
                                @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Create username</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-8">
                    <?php $cards = \App\Card::all(); ?>
                    <div class="account-box">
                    <h2>Your Usernames</h2>
                    <p>
                        New rules everyone! You are only allowed:<br/>
                    </p>
                    <ul>
                        <li>Tiberian Sun players are now allowed up to 3 nicknames per month.</li>
                        <li>Red Alert &amp; Yuri's Revenge players are only allowed 1 nickname per month.</li>
                        <li>One user account, having multiple accounts are not allowed. </li>
                    </ul>
                    <p>
                        To use your nickname, activate it and it will appear in your Quick Match client.
                    </p>

                    <div class="account-player-listings">
                    @foreach($user->usernames()
                        ->orderBy("ladder_id", "DESC")
                        ->orderBy("id", "DESC")
                        ->get() as $u)

                        <?php 
                            $player = \App\Player::where("username", $u->username)
                                ->where("ladder_id", $u->ladder_id)
                                ->first();

                            $date = \Carbon\Carbon::now();
                            $startOfMonth = $date->startOfMonth()->toDateTimeString();
                            $endOfMonth = $date->endOfMonth()->toDateTimeString();

                            $activeHandle = \App\PlayerActiveHandle::getPlayerActiveHandle($player->id, $u->ladder_id, 
                                $startOfMonth, $endOfMonth);
                        ?>

                        <div class="player-listing {{$activeHandle ? 'active': ''}}">
                            <div class="username">
                                <i class="icon icon-game icon-{{ $u->ladder()->first()->abbreviation }}"></i>  
                                <div>
                                    {{ $u->username }}
                                </div>
                            </div>

                            <div class="card">
                                <p>Ladder player card</p>
                                <form id="playerCard" class="form-inline" method="POST" action="account/card">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="playerId" value="{{ $u->id }}" />
                                    
                                    <select class="form-control" name="cardId">
                                        @foreach($cards as $card)
                                            <option value="{{ $card->id }}" @if($card->id == $u->card_id) selected @endif>
                                            {{ $card->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-submit">Save</button>
                                </form>
                            </div>


                            <div class="username-status">
                                <p>
                                    {{ 
                                        $activeHandle 
                                        ? 
                                            "This username will appear in your Quick Match client. " 
                                        : 
                                        "Click Play to add this username to your Quick Match client"
                                    }}
                                </p>
                                <form id="usernameStatus" class="form-inline" method="POST" action="account/username-status">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="username" value="{{ $u->username }}" />
                                    <input type="hidden" name="ladderId" value="{{ $u->ladder_id}}" />

                                    <button type="submit" class="btn btn-activate">
                                        {{ $activeHandle ? "Deactivate": "Play with username"}}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
