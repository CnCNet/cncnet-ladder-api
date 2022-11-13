@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
    /images/feature/feature-index.jpg
@endsection

@section('feature')
    <div class="px-4 py-5 text-center" style="background: #0e0f16;">
        <img class="d-block mx-auto mb-4" src="/images/cncnet-logo.png" alt="">
        <h1 class="display-5 fw-bold">
            CnCNet Ladders
        </h1>
        <div class="col-lg-6 mx-auto">
            <p class="lead mb-4">
                Play, Complete, Conquer
            </p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <button type="button" class="btn btn-primary px-4 gap-3">Register</button>
                <button type="button" class="btn btn-secondary px-4">Login</button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="light-texture game-detail supported-games">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h3>CnCNet <strong>Ranked Match</strong></h3>
                </div>
            </div>
            <div class="feature">
                <div class="row">
                    @foreach ($ladders as $history)
                        <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                            <a href="/ladder/{{ $history->short . '/' . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="ladder-link">
                                <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover.png' }}')">
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
    </section>

    <section class="dark-texture game-detail supported-games">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h3>Recent <strong>Ladder Champions</strong></h3>
                </div>
            </div>

            @foreach ($ladders_winners as $ladderWinners)
                <?php $date = \Carbon\Carbon::parse($ladderWinners['ends']); ?>
                <div class="feature">

                    <div class="row">
                        @foreach ($ladderWinners['players'] as $k => $player)
                            <?php $url = '/ladder/' . $ladderWinners['short'] . '/' . $ladderWinners['abbreviation'] . '/player/' . $player->player_name; ?>

                            <div class="col-xs-12 col-md-6">
                                <h4><a href="/ladder-champions/{{ $ladderWinners['abbreviation'] }}">View All</a> <strong>Past Ladder Champions</strong></h4>
                                <a href="{{ $url }}" title="View {{ $player->player_name }}'s profile">
                                    <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}" style="background-image: url('/images/ladder/{{ $ladderWinners['game'] }}-cover-masters.png">
                                        <div class="details tier-league-cards">
                                            <div class="type">
                                                <h1 class="lead"><strong>{{ $player->player_name }}</strong></h1>
                                                <h2><strong>Rank #{{ $k + 1 }}</strong></h2>
                                                <ul class="list-inline" style="font-size: 14px;">
                                                    <li>
                                                        Wins
                                                        <i class="fa fa-level-up"></i> {{ $player->wins }}
                                                    </li>
                                                    <li>
                                                        Games
                                                        <i class="fa fa-diamond"></i> {{ $player->games }}
                                                    </li>
                                                </ul>
                                                @if ($k > 0)
                                                    <small>Runner up of the
                                                    @else
                                                        <small>Champion of the
                                                @endif
                                                <strong>{{ $date->format('m/Y') }}</strong> {{ $ladderWinners['full'] }} Ladder</small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>
    </section>

@endsection
