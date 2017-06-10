@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-{{ $ladder->abbreviation }}.jpg
@endsection

@if($ladder->abbreviation == "ra")
    <?php $dir = "red-alert"; ?>
@elseif($ladder->abbreviation == "ts")
    <?php $dir = "tiberian-sun"; ?>
@elseif($ladder->abbreviation == "yr")
    <?php $dir = "yuris-revenge"; ?>
@endif

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    <img src="/images/games/{{ $dir }}/logo.png" class="logo" />
                </h1>
                <p class="text-uppercase">
                   Play. Compete. <strong>Conquer.</strong>
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
                    <div class="text-center" style="padding-bottom: 40px;">
                        <h1>{{ $ladder->name }}</h1>
                        <p class="lead">Find the latest competitive games</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th class="rank">Rank <i class="fa fa-trophy fa-fw"></i></th>
                                <th>Profile <i class="fa fa-user fa-fw"></i></th>
                                <th class="hidden-xs">Points <i class="fa fa-bolt fa-fw"></i></th>
                                <th>Won <i class="fa fa-level-up fa-fw"></i></th>
                                <th>Lost <i class="fa fa-level-down fa-fw"></i></th>
                                <th>Win % </th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($players as $k => $player)
                            <tr>
                                <td>#{{ $k + 1 }}</td>
                                <td>
                                    <a href="/ladder/{{ $ladder->abbreviation }}/player/{{ $player->username }}">{{ $player->username }}</a>
                                </td>
                                <td>{{ $player->points }}</td>
                                <td>{{ $player->win_count }}</td>
                                <td>{{ $player->loss_count }}</td>
                                <td>
                                @if($player->win_count > 0)  
                                    {{ number_format($player->win_count / ($player->win_count + $player->loss_count) * 100) }}%
                                @else
                                    0%
                                @endif
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

@endsection

