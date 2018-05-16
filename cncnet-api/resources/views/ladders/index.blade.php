@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-index.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet <strong>Ladders</strong>
                </h1>
                <p>
                   Play, Compete, <strong>Conquer!</strong>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="light-texture game-detail supported-games">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h3>CnCNet <strong>Quick Match</strong> </h3>
            </div>
        </div>
        <div class="feature">
            <div class="row">
                @foreach($ladders as $history)
                <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                    <a href="/ladder/{{ $history->short . "/" . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="ladder-link">
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
</section>

<section class="dark-texture game-detail supported-games">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h3>Recent <strong>Ladder Champions</strong></h3>
            </div>
        </div>

            @foreach($ladders_winners as $ladderWinners)
            <?php $date = \Carbon\Carbon::parse($ladderWinners["ends"]); ?>
            <div class="feature">
                
                <div class="row">
                @foreach($ladderWinners["players"] as $k => $player)
                <?php $url = "/ladder/". $ladderWinners["short"] . "/" . $ladderWinners["abbreviation"] . "/player/" . $player->username; ?>
                
                <div class="col-xs-12 col-md-6">
                    <h4><a href="/ladder-champions/{{ $ladderWinners['abbreviation']}}">View All</a> <strong>Past Ladder Champions</strong></h4>
                    <a href="{{ $url }}" title="View {{$player->username}}'s profile">
                        <div class="ladder-cover cover-{{ $history->ladder->abbreviation}}" style="background-image: url('/images/ladder/{{ $ladderWinners["game"]}}-cover-masters.png">
                            <div class="details tier-league-cards">
                                <div class="type">
                                    <h1 class="lead"><strong>{{ $player->username }}</strong></h1>
                                    <h2><strong>Rank #{{ $k+1 }}</strong></h2>
                                    <ul class="list-inline" style="font-size: 14px;">
                                        <li>
                                            Wins 
                                            <i class="fa fa-level-up"></i> {{ $player->total_wins }}
                                        </li>
                                        <li>
                                            Games 
                                            <i class="fa fa-diamond"></i> {{ $player->total_games }}
                                        </li>
                                    </ul>
                                    @if ($k>0)
                                    <small>Runner up of the 
                                    @else
                                    <small>Champion of the 
                                    @endif
                                    <strong>{{ $date->format("m/Y") }}</strong> {{ $ladderWinners["full"] }} Ladder</small>
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