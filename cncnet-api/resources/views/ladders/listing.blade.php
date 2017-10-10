@extends('layouts.app')
@section('title', $history->ladder->name . ' Ladder')

@section('cover')
/images/feature/feature-{{ $history->ladder->abbreviation }}.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    {{ $history->ladder->name }}
                </h1>
                <p>
                    CnCNet Ladders <strong>1vs1</strong>
                </p>
                <p>
                    <a href="/ladder" class="previous-link">
                        <i class="fa fa-caret-left" aria-hidden="true"></i>
                        <i class="fa fa-caret-left" aria-hidden="true"></i>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="cncnet-features general-texture game-detail">
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="header">
                    <div class="col-md-12">
                        <h3><strong>1vs1</strong> Recent Games</h3>
                    </div>
                </div>
            </div>
        </div>

        @include("components.global-recent-games", ["games" => $games])

        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="header">
                        <div class="row">
                            <div class="col-md-6">
                                <h3><strong>1vs1</strong> Battle Rankings</h3>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="btn-group filter">
                                    <button type="button" class="btn btn-transparent dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Previous Month <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                    @foreach($ladders_previous as $previous)
                                    <li>
                                        <a href="/ladder/{{ $previous->short . "/" . $previous->ladder->abbreviation }}/" title="{{ $previous->ladder->name }}">
                                            {{ $previous->short }}
                                        </a>
                                    </li>
                                    @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                    @foreach($players as $k => $player)
                        <div class="col-md-4">
                            @include("components/player-box", 
                            [
                                "username" => $player->username,
                                "points" => $player->points,
                                "badge" => $player->badge($player->points), 
                                "rank" => $k + 1,
                                "wins" => $player->wins($history),
                                "totalGames" => $player->totalGames($history),
                                "playerCard" => isset($player->card->short) ? $player->card->short : "", 
                                "url" => "/ladder/". $history->short . "/" . $history->ladder->abbreviation . "/player/" . $player->username
                            ])
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection