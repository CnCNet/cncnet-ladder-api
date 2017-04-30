@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-td.jpg
@endsection


@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet Ladders
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
<section class="light-texture game-detail supported-games">
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center" style="padding-bottom: 40px;">
                        <h1>Ready to face the competition?</h1>
                        <p class="lead">Find the latest competitive ladders below.</p>
                    </div>

                    @foreach($ladders as $k => $ladder)
                    <div class="col-md-4">
                        <div class="box">
                            <a href="/ladder/{{ $ladder->abbreviation }}/" title="Tiberian Dawn" class="game-cover game-{{ $ladder->abbreviation }}">
                                <span class="image"></span>
                                <span class="sr-only">{{ $ladder->name }}</span>
                            </a>
                            <div class="description">
                                <h3>{{ $ladder->name }} </h3>
                                <a class="btn btn-tertiary btn-md" href="/ladder/{{ $ladder->abbreviation }}/">Go to Ladder</a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

