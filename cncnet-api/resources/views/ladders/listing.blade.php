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
        @include("components.stats", [$stats])

        <?php $date = \Carbon\Carbon::parse($history->ends); ?>

        @if ($date->isPast() && ($search === null || $search == ""))
        <h2><strong>{{ $date->format("m/Y") }}</strong> League Champions!</h2>
        <div class="feature">
            <div class="row">
                <?php $winners = $players->slice(0,2); ?>
                @foreach($winners as $k => $winner)
                <div class="col-xs-12 col-md-6">
                    <div class="ladder-cover cover-{{ $history->ladder->abbreviation}}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . "-cover-masters.png" }}')">
                        <div class="details tier-league-cards">
                            <div class="type">
                                <h1 class="lead"><strong>{{ $winner->player_name }}</strong></h1>
                                <h2><strong>Rank #{{ $k+1 }}</strong></h2>
                                <ul class="list-inline" style="font-size: 14px;">
                                    <li>
                                        Wins
                                        <i class="fa fa-level-up"></i> {{ $winner->wins }}
                                    </li>
                                    <li>
                                        Games
                                        <i class="fa fa-diamond"></i> {{ $winner->games }}
                                    </li>
                                </ul>
                                @if ($k>0)
                                <small>Runner up of the
                                @else
                                <small>Champion of the
                                @endif
                                <strong>{{ $date->format("m/Y") }}</strong> {{ $history->game }} League</small>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="feature">
            <div class="row">
                <div class="header">
                    <div class="col-md-12">
                        <h3><strong>1vs1</strong> Recent Games
                            <small>
                                <a href="{{"/ladder/". $history->short . "/" . $history->ladder->abbreviation . "/games"}}">View All Games</a>
                            </small>
                        </h3>
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
                            <div class="col-md-4">
                                @if($history->ladder->qmLadderRules->tier2_rating > 0)
                                    @if($tier == 1 || $tier === null)
                                        <h3><strong>1vs1</strong> Masters League Rankings</h3>
                                    @elseif($tier == 2)
                                        <h3><strong>1vs1</strong> Contenders League Rankings</h3>
                                    @endif
                                @else
                                    <h3><strong>1vs1</strong> Battle Rankings</h3>
                                @endif
                            </div>

                            <div class="col-md-8 text-right">
                                <ul class="list-inline">
                                    <li>
                                        <button class="btn btn-secondary text-uppercase" data-toggle="modal" data-target="#battleRanks" style="font-size: 15px;">
                                            <i class="fa fa-trophy fa-lg fa-fw" aria-hidden="true" style="margin-right: 5px;"></i> Battle Ranks
                                        </button>
                                    </li>
                                    <li>
                                        <div class="btn-group filter">
                                            <button type="button" class="btn btn-secondary dropdown-toggle text-uppercase" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size: 15px;">
                                                <i class="fa fa-industry fa-fw" aria-hidden="true" style="margin-right: 5px;"></i> Previous Month <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu">
                                            @foreach($ladders_previous as $previous)
                                            <li>
                                                <a href="/ladder/{{ $previous->short . "/" . $previous->ladder->abbreviation }}/" title="{{ $previous->ladder->name }}">
                                                    Rankings - {{ $previous->short }}
                                                </a>
                                            </li>
                                            @endforeach
                                            </ul>
                                        </div>
                                    </li>
                                    <li>
                                        <form>
                                            <div class="form-group" method="GET">
                                                <div class="search" style="position:relative;">
                                                    <label for="search-input" style="position: absolute;left: 12px;top: 7px;">
                                                        <i class="fa fa-search" aria-hidden="true"></i>
                                                    </label>
                                                    <input class="form-control" name="search" value="{{ $search }}" placeholder="Player username..." style="padding-left:40px;"/>
                                                </div>
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                                <div class="text-right">
                                    @if ($search)
                                        <small>
                                            Searching for <strong>{{ $search }}</strong> returned {{ count($players) }} results
                                            <a href="?search=">Clear?</a>
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($history->ladder->qmLadderRules->tier2_rating > 0)
                    <div class="feature" style="margin-top: -25px;">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-6" style="margin-bottom:20px">
                                <a href="/ladder/{{ $history->short . "/1/" . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="ladder-link">
                                    <div class="ladder-cover cover-{{ $history->ladder->abbreviation}}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . "-cover-masters.png" }}')">
                                        <div class="details tier-league-cards">
                                            <div class="type">
                                                <h1>Masters <strong>League</strong></h1>
                                                <p class="lead">1<strong>vs</strong>1</p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-6" style="margin-bottom:20px">
                                <a href="/ladder/{{ $history->short . "/2/" . $history->ladder->abbreviation }}/" title="{{ $history->ladder->name }}" class="ladder-link">
                                    <div class="ladder-cover cover-{{ $history->ladder->abbreviation}}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . "-cover-contenders.png" }}')">
                                        <div class="details tier-league-cards">
                                            <div class="type">
                                                <h1>Contenders <strong>League</strong></h1>
                                                <p class="lead">1<strong>vs</strong>1</p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <?php $perPage = $players->perPage(); $rankOffset = ($players->currentPage() * $perPage) - $perPage; ?>

                    <div class="row">
                        <div class="col-md-12 text-center">
                        {!! $players->render() !!}
                        </div>
                    </div>

                    <div class="row">
                        @foreach($players as $k => $player)
                        <?php
                            $rank = ($rankOffset + $k) + 1;
                            if ($search)
                                $rank = null;
                        ?>

                            <?php 

                                $countryName = "";
                                $side = null;
                                
                                if ( $history->ladder->abbreviation == "yr")
                                {
                                    $side = \App\Side::where("local_id", $player->country)
                                    ->where("ladder_id", $history->ladder->id)
                                    ->first(); 
                                }
                                else
                                {
                                    if ($player->side !== null)
                                    {
                                        if(array_key_exists($player->side, $sides))
                                        {
                                            $countryName = $sides[$player->side];
                                        }
                                    }
                                }

                                if ($side !== null)
                                {
                                    $countryName = $side->name; 
                                }
                            ?>

                            <div class="col-md-4">
                                @include("components/player-box",
                                [
                                    "username" => $player->player_name,
                                    "points" => $player->points,
                                    "badge" => \App\Player::getBadge($player->percentile),
                                    "rank" => $rank,
                                    "wins" => $player->wins,
                                    "totalGames" => $player->games,
                                    "playerCard" => $player->card !== null ? (array_key_exists($player->card, $cards) ? $cards[$player->card + 0] : "") : "",
                                    "side" => $countryName,
                                    "url" => "/ladder/". $history->short . "/" . $history->ladder->abbreviation . "/player/" . $player->player_name,
                                    "game" => $history->ladder->abbreviation
                                ])
                            </div>
                        @endforeach
                    </div>

                    <div class="row">
                        <div class="col-md-12 text-center">
                        {!! $players->render() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Battle Ranks -->
<div class="modal fade" id="battleRanks" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Battle Ranks <small class="text-uppercase">What rank am I?</small></h3>
            </div>
            <div class="modal-body clearfix text-center">
            <?php $pecentiles = [15, 25, 45, 55, 65, 75, 85, 90, 100]; ?>
            <?php $player = new \App\Player(); ?>
            @foreach($pecentiles as $percentile)
                <?php $badge = $player->badge($percentile); ?>
                <p class="lead">{{ $badge["type"] }}</p>
                <div class="player-badge badge-2x" style="margin: 0 auto; height: 150px;">
                    <img src="/images/badges/{{ $badge["badge"] . ".png" }}" style="height:150px;">
                </div>
                <hr>
            @endforeach
            </div>
            <div class="modal-footer" style="border:none;">
                <button type="button" class="btn btn-primary btn-lg" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
