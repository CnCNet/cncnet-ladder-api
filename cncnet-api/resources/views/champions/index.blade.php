@extends('layouts.app')
@section('title', 'League Champions')

@section('cover')
/images/feature/feature-index.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet <strong>Ladder Champions</strong>
                </h1>
                <p>
                   Past winners of the monthly CnCNet Ladder competitions
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
            <div class="col-md-12 text-center">
            <?php 
                $img = "";
                switch($ladders_winners[0]["abbreviation"])
                {
                    case "yr":
                        $img = "//cncnet.org/images/games/yuris-revenge/logo.png";
                        break;     
                    case "ts":
                        $img = "//cncnet.org/images/games/tiberian-sun/logo.png";
                        break;
                    case "ra":
                        $img = "//cncnet.org/images/games/red-alert/logo.png";
                        break;
                }
                ?>
                <div class="text-center">
                    <img src="{{ $img }}" style="max-width: 100%;"/>
                </div>
            </div>
        </div>
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                @foreach($ladders_winners as $ladderWinners)
                <?php $date = \Carbon\Carbon::parse($ladderWinners["ends"]); ?>
                <div>
                    <h3>{{ $date->format("F Y") }} <strong>Ladder Champions</strong></h3>
                
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                <th>Points</th>
                                <th>Won</th>
                                <th>Lost</th>
                                <th>Total Games Played</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ladderWinners["players"] as $k => $player)
                            <?php $url = "/ladder/". $ladderWinners["short"] . "/" . $ladderWinners["abbreviation"] . "/player/" . $player->username; ?>
                            <tr>
                                <th scope="row">{{ $k + 1 }}</th>
                                <td>
                                <a href="{{ $url}}" target="_blank">
                                {{ $player->username }}
                                </a>
                                </td>
                                <td>{{ $player->points }}</td>
                                <td>{{ $player->total_wins }}</td>
                                <td>{{ $player->total_games-$player->total_wins }}</td>
                                <td>{{ $player->total_games }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@endsection