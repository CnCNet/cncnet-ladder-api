@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-{{ $history->ladder->abbreviation }}.jpg
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
<?php $card = \App\Card::find($player->player->card_id); ?>

<div class="player">
    <div class="feature-background player-card {{ $card->short or "no-card" }}">
        <div class="container">

            <div class="player-header">
                <div class="player-stats">

                    <h1 class="username">
                        {{ $player->username }}
                    </h1>
                    <ul class="list-inline text-uppercase">
                        <li>
                            Points <strong> {{ $player->points }} </strong>
                            <i class="fa fa-bolt fa-fw fa-lg"></i>
                        </li>
                        <li>
                            Wins <strong>{{ $player->games_won }}</strong>
                            <i class="fa fa-level-up fa-fw fa-lg"></i>
                        </li>
                        @if($userIsMod)
                            <li>
                                <a href="/admin/moderate/{{ $ladderId }}/player/{{ $player->id }}" class="btn btn-sm btn-danger">Moderation Actions</a>
                            </li>
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
                        #{{ $player->rank == -1 ? "Unranked" : $player->rank }}
                    </h1>
                    <?php $badge = $player->badge; ?>
                    <div class="player-badge badge-2x">
                        @if (strlen($badge->badge) > 0)
                        <img src="/images/badges/{{ $badge->badge . ".png" }}">
                        <p class="lead text-center">{{ $player->badge->type }}</p>
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
                    "gamesCount" => $player->game_count,
                    "averageFps" => $player->average_fps,
                    "gamesWon" => $player->games_won,
                    "gamesLost" => $player->games_lost,
                    "gamesCount" => $player->game_count
                ])
                </div>
                <div class="player-achievements">
                    <h3 class="title">Achievements</h3>

                    <div class="achievements-list">
                        @if ($player->game_count >= 200)
                        <div class="achievement">
                            <img src="/images/badges/achievement-games.png" style="height:50px"/>
                            <h5 style="font-weight: bold; text-transform:uppercase; font-size: 10px;">Played <br/>200+ Games</h5>
                        </div>
                        @endif
                        @if ($player->game_count >= 300)
                        <div class="achievement">
                            <img src="/images/badges/achievement-games.png" style="height:50px"/>
                            <h5 style="font-weight: bold; text-transform:uppercase; font-size: 10px;">Played <br/>300+ Games</h5>
                        </div>
                        @endif

                        @if ($player->rank <= 10)
                        <div class="achievement">
                            @if ($player->rank == 1)
                                <img src="/images/badges/achievement-rank1.png" style="height:50px"/>
                                <h5 class="gold">Rank #1 Player</h5>
                            @elseif($player->rank > 1 && $player->rank <=5)
                                <img src="/images/badges/achievement-top5.png" style="height:50px"/>
                                <h5 class="silver">Top 5 Player</h5>
                            @elseif($player->rank > 5 && $player->rank <=10)
                                <img src="/images/badges/achievement-top10.png" style="height:50px"/>
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

                    @include("components.player-recent-games", ["player" => $player, "games" => $games])

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
