@extends('layouts.app')
@section('title', 'Badges')

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
                   Player <strong>Badges</strong>
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
                    <div class="text-left">
                        <h1>1vs1 Player Badges</h1>
                    </div>

                    <?php $player = new \App\Player(); ?>
                    <div class="row">
                    <?php $points = 0; ?>
                    @for($i = 0; $i < 15; $i++)
                        <?php $points += 100; ?>
                        <div class="col-md-3">
                            <h3>Points: {{ $points }}  </h3>
                            <div class="rank">
                                <i class="rank {{ $player->badge($points) }}"></i> 
                            </div>
                        </div>
                    @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

