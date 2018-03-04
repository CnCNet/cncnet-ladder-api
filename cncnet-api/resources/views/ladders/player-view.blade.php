@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-{{ $history->ladder->abbreviation }}.jpg
@endsection

@section('feature')
<div class="player">
    <div class="feature-background">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h1>
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
                    </ul>
                </div>
                <div class="col-md-6 text-right">
                    <h1 class="rank"><span class="text-uppercase">Rank</span> #{{ $player->rank == -1 ? "Unranked" : $player->rank }}</h1>
                    <?php $badge = $player->badge; ?>
                    <div class="player-badge badge-2x" style="margin: 0 auto;">
                        @if (strlen($badge->badge) > 0)
                        <img src="/images/badges/{{ $badge->badge . ".png" }}">
                        <p class="lead text-center" style="margin-top: 15px;">{{ $player->badge->type }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="player">

    <section class="player-statistics">
        <div class="profile">
            <div class="container">
                <div class="row">
                    <div class="col-md-8">
                        @include("components.dials", [
                            "gamesCount" => $player->game_count,
                            "averageFps" => $player->average_fps,
                            "gamesWon" => $player->games_won,
                            "gamesLost" => $player->games_lost,
                            "gamesCount" => $player->game_count
                        ])
                    </div>
                    <div class="col-md-4">
                    </div>
                </div>
            </div>
        </div>
    </section>

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