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
                    <h3><strong>Psst...</strong> Points system has been updated for December Ladders!</h3>
                    <p class="lead">Read more about the changes <a href="https://forums.cncnet.org/topic/7847-some-changes-to-the-ladder-for-december-2017/">in our forums</a></p>
                </div>

                <div class="header">
                    <div class="col-md-12">
                        <h3><strong>1vs1</strong> Games</h3>
                    </div>
                </div>
            </div>
        </div>

        @include("components.global-recent-games", ["games" => $games])
        
        <div class="row">
            <div class="col-md-12 text-center">
            {!! $games->render() !!}
            </div>
        </div>
    </div>
</section>

@endsection