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
                <div class="col-md-12">
                    <h3><strong>Follow all Ladder Changes</strong> <a href="https://forums.cncnet.org/forum/66-cncnet-ladder/">in our forum</a></h3>
                </div>

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
                            <div class="col-md-6">
                                <ul class="list-inline">
                                    <li>
                                        <button class="btn btn-secondary btn-lg text-uppercase" data-toggle="modal" data-target="#battleRanks">
                                            <i class="fa fa-trophy fa-lg fa-fw" aria-hidden="true" style="margin-right: 5px;"></i> Battle Ranks
                                        </button>
                                    </li>
                                    <li>
                                        <div class="btn-group filter">
                                            <button type="button" class="btn btn-secondary btn-lg text-uppercase dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
                                </ul>
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

                    <div class="header">
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

                    <div class="row">
                        @foreach($players as $k => $player)
                        <div class="col-md-4">
                            @include("components/player-box",
                            [
                                "username" => $player->username,
                                "points" => $player->points,
                                "badge" => $player->badge(),
                                "rank" => ($rankOffset + $k) + 1,
                                "wins" => $player->total_wins,
                                "totalGames" => $player->total_games,
                                "playerCard" => isset($player->card->short) ? $player->card->short : "",
                                "url" => "/ladder/". $history->short . "/" . $history->ladder->abbreviation . "/player/" . $player->username
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title">Battle Ranks <small>What rank am I?</small></h3>
            </div>
            <div class="modal-body clearfix text-center">
            <?php $pecentiles = [15, 25, 45, 55, 65, 75, 85, 90, 100]; ?>
            <?php $player = new \App\Player(); ?>
            @foreach($pecentiles as $percentile)
                <?php $badge = $player->badge($percentile); ?>
                <p class="lead">{{ $badge["type"] }}</p>
                <div class="player-badge badge-2x" style="margin: 0 auto;">
                    <img src="/images/badges/{{ $badge["badge"] . ".png" }}">
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