@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-{{ $history->ladder->abbreviation }}.jpg
@endsection

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
@endsection

@section('feature')
<div class="game">
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
                        <a href="{{ "/ladder/". $history->short . "/" . $history->ladder->abbreviation }}" class="previous-link">
                            <i class="fa fa-caret-left" aria-hidden="true"></i>
                            <i class="fa fa-caret-left" aria-hidden="true"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<?php $card = \App\Card::find($ladderPlayer->player->card_id); ?>

<div class="player">
    <div class="feature-background player-card {{ $card->short or "no-card" }}">
        <div class="container">

            <div class="player-header">
                <div class="player-stats">

                    <h1 class="username">
                        {{ $ladderPlayer->username }}
                    </h1>
                    <ul class="list-inline text-uppercase">
                        <li>
                            Points <strong> {{ $ladderPlayer->points }} </strong>
                            <i class="fa fa-bolt fa-fw fa-lg"></i>
                        </li>
                        <li>
                            Wins <strong>{{ $ladderPlayer->games_won }}</strong>
                            <i class="fa fa-level-up fa-fw fa-lg"></i>
                        </li>
                        @if($userIsMod)
                        <li>
                            <a href="/admin/moderate/{{ $ladderId }}/player/{{ $ladderPlayer->id }}" class="btn btn-sm btn-danger">Moderation Actions</a>
                        </li>
                        @endif

                        @if(isset($mod) && $mod->isLadderAdmin($player['ladder']))
                        <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#submitLaundryService">Laundry Service</button>

                        <div class="modal fade" id="submitLaundryService" tabIndex="-1" role="dialog">
                            <div class="modal-dialog modal-md" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h3 class="modal-title">Submit Laundry Service</h3>
                                    </div>
                                    <div class="modal-body clearfix">
                                        <div class="container-fluid">
                                            <div class="row content">
                                                <div class="col-md-12 player-box player-card list-inline">

                                                    @if(!$player->laundered($history))
                                                    <label>Are you sure you want to set all of {{$player->username}}'s points to 0?</label>
                                                    @endif
                                                    
                                                    <div style="display: inline-block">
                                                        <form method="POST" action="/admin/moderate/{{$player->ladder->id}}/player/{{$player->id}}/laundry">
                                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                            <input name="player_id" type="hidden" value="{{ $player->id }}" />
                                                            <input name="ladderHistory_id" type="hidden" value="{{ $history->id }}" />
                                                            <button type="submit" name="submit" value="update" class="btn btn-danger btn-md">Launder</button>
                                                        </form>
                                                    </div>

                                                    @if($player->laundered($history))
                                                    <div style="padding-top: 5px; display: inline-block">
                                                        <form method="POST" action="/admin/moderate/{{$player->ladder->id}}/player/{{$player->id}}/undoLaundry">
                                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                            <input name="player_id" type="hidden" value="{{ $player->id }}" />
                                                            <input name="ladderHistory_id" type="hidden" value="{{ $history->id }}" />
                                                            <button type="submit" name="submit" value="update" class="btn btn-primary btn-sm">Undo Launder</button>
                                                        </form>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </ul>
                </div>

                <div class="player-alerts">
                    @if(count($bans))
                    <h3> Bans: </h3>
                    <ul>
                        @foreach($bans as $ban)
                        <li>{{ $ban }}</li>
                        @endforeach
                    </ul>
                    @endif
                    @if(count($alerts))
                    <h3> Alerts:</h3>
                    <ul>
                        @foreach($alerts as $alert)
                        <li>{!! $alert->message !!}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                <div class="player-badges">
                    <h1 class="rank text-center">
                        <span class="text-uppercase">Rank</span>
                        #{{ $ladderPlayer->rank == -1 ? "Unranked" : $ladderPlayer->rank }}
                    </h1>
                    <?php $badge = $ladderPlayer->badge; ?>
                    <div class="player-badge badge-2x">
                        @if (strlen($badge->badge) > 0)
                        <img src="/images/badges/{{ $badge->badge . ".png" }}">
                        <p class="lead text-center">{{ $ladderPlayer->badge->type }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="feature-footer-background">
        <div class="container">
            <div class="player-footer">
                <div class="player-dials">
                    @include("components.dials", [
                    "gamesCount" => $ladderPlayer->game_count,
                    "averageFps" => $ladderPlayer->average_fps,
                    "gamesWon" => $ladderPlayer->games_won,
                    "gamesLost" => $ladderPlayer->games_lost,
                    "gamesCount" => $ladderPlayer->game_count
                    ])
                </div>
                <div class="player-achievements">

                <canvas id="gamesPlayed" width="350" height="250" style="margin-top: 15px;"></canvas>

                <script>
                    const config = {
                        type: "bar",
                        data: {
                            labels: {!! json_encode($graphGamesPlayedByWeek["labels"]) !!},
                            datasets: [
                                {
                                    label: "Won",
                                    data: {!! json_encode($graphGamesPlayedByWeek["data_games_won"]) !!},
                                    backgroundColor: "rgba(64, 206, 0, 1",
                                },
                                {
                                    label: "Lost",
                                    data: {!! json_encode($graphGamesPlayedByWeek["data_games_lost"]) !!},
                                    backgroundColor: "rgba(0, 0, 255, 0.9)",
                                },
                            ]
                        },
                        options: {
                            scales: {
                                x: {
                                    stacked: true,
                                    type: "time"
                                },
                                y: {
                                    stacked: true
                                }
                            },
                            responsive: true,
                            scale: {
                                ticks: {
                                    precision: 0
                                },
                            },
                        }
                    };
                    const ctx = document.getElementById("gamesPlayed");
                    const myChart = new Chart(ctx, config);
                </script>

                <h3 class="title">Achievements</h3>

                <div class="achievements-list">
                    @if ($ladderPlayer->game_count >= 200)
                    <div class="achievement">
                        <img src="/images/badges/achievement-games.png" style="height:50px" />
                        <h5 style="font-weight: bold; text-transform:uppercase; font-size: 10px;">Played <br />200+ Games</h5>
                    </div>
                    @endif
                    @if ($ladderPlayer->game_count >= 300)
                    <div class="achievement">
                        <img src="/images/badges/achievement-games.png" style="height:50px" />
                        <h5 style="font-weight: bold; text-transform:uppercase; font-size: 10px;">Played <br />300+ Games</h5>
                    </div>
                    @endif

                    @if ($ladderPlayer->rank <= 10) 
                    <div class="achievement">
                        @if ($ladderPlayer->rank == 1)
                        <img src="/images/badges/achievement-rank1.png" style="height:50px" />
                        <h5 class="gold">Rank #1 Player</h5>
                        @elseif($ladderPlayer->rank > 1 && $ladderPlayer->rank <=5) 
                        <img src="/images/badges/achievement-top5.png" style="height:50px" />
                        <h5 class="silver">Top 5 Player</h5>
                        @elseif($ladderPlayer->rank > 5 && $ladderPlayer->rank<=10) <img src="/images/badges/achievement-top10.png" style="height:50px" />
                        <h5 class="bronze">Top 10 Player</h5>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>


<div class="player">
    <section class="dark-texture">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h3>Recently played</h3>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            {!! $games->render() !!}
                        </div>
                    </div>

                    @include("components.player-recent-games", ["player" => $ladderPlayer, "games" => $games])

                    <div class="row">
                        <div class="col-md-12 text-center">
                            {!! $games->render() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection

@if ($history->ends > Carbon\Carbon::now())
@include('components.countdown', ['target' => $history->ends->toISO8601String() ])
@endif