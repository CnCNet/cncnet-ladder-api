@extends('layouts.app')
@section('title', 'League Champions')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="{{ \App\URLHelper::getLadderLogoByAbbrev($abbreviation) }}" style="max-width: 100%;" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $ladder->name }}</strong> <br />
                        <span>Ladder Champions</span>
                    </h1>

                    <p class="lead text-uppercase">
                        <strong>1 vs 1 Ranked Match</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    @foreach ($ladders_winners as $ladderWinners)
                        <?php $date = \Carbon\Carbon::parse($ladderWinners['ends']); ?>

                        <div>
                            <h3 class="pb-5 pt-5">{{ $date->format('F Y') }} <strong>Ladder Champions</strong></h3>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Player</th>
                                        <th>Points</th>
                                        <th>Won</th>
                                        <th>Lost</th>
                                        <th>Total Games Played</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ladderWinners['players'] as $k => $player)
                                        <?php $url = '/ladder/' . $ladderWinners['short'] . '/' . $ladderWinners['abbreviation'] . '/player/' . $player->player_name; ?>
                                        <tr>
                                            <th scope="row">#{{ $k + 1 }}</th>
                                            <td>
                                                <a href="{{ $url }}" target="_blank">
                                                    {{ $player->player_name }}
                                                </a>
                                            </td>
                                            <td>{{ $player->points }}</td>
                                            <td>{{ $player->wins }}</td>
                                            <td>{{ $player->games - $player->wins }}</td>
                                            <td>{{ $player->games }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
