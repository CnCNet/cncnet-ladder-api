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

        <div class="row">
            <div class="col-md-12">
                <div class="row">

                    <div class="col-md-6">
                        <h3>Deets</h3>
                        {{ $game }}
                        <h3>Map</h3>
                        <img src="https://grant.cnc-comm.com/tmp/d179f3b4590e7f014d0761d670c5374de9458ef5.png" class="img-responsive">
                    </div>

                    @foreach($stats as $k => $stat)

                    <?php $gameStats = \App\Stats::where("id", "=", $stat->stats_id)->first(); ?>
                    <?php $player = \App\Player::where("id", "=", $stat->player_id)->first(); ?>

                    <div class="col-md-12">
                        <h3>Player {{$k + 1}} </h3>
                        <p>{{ $player->username }}</p>
                        {{ $player }}

                        <div class="rank-title gold">
                            1st <i class="fa fa-trophy fa-fw fa-2x"></i>
                        </div>

                        <div class="stats clearfix" style="background: black; font-size: 10px;">
                            
                            Colour: {{ $gameStats->col }}               <br>
                            Country: {{ $gameStats->cty }}              <br>
                            Credits: {{ $gameStats->crd }}              <br>
                            Infantry Left: {{ $gameStats->inl }}        <br>
                            Planes Left: {{ $gameStats->pll }}          <br>
                            Buildings Left: {{ $gameStats->bll }}       <br>
                            Units Bought: {{ $gameStats->unb }}         <br>
                            Infantry Bought: {{ $gameStats->inb }}      <br>
                            Planes Bought: {{ $gameStats->plb }}        <br>
                            Buildings Bought: {{ $gameStats->blb }}     <br>
                            Units Bought: {{ $gameStats->unk }}         <br>
                            Infantry Bought: {{ $gameStats->ink }}      <br>
                            Planes Killed: {{ $gameStats->plk }}        <br>
                            Buildings Destroyed: {{ $gameStats->blk }}  <br>
                            Buildings Captured: {{ $gameStats->blc }}   <br>
                            Crates Found: {{ $gameStats->cra }}         <br>
                            Harvested: {{ $gameStats->hrv }}            <br>
                        </div>
                    </div>

                    @endforeach
                </div>
            </div>
    </div>
</section>

@endsection
