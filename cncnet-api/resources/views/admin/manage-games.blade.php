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
                    CnCNet Admin
                </h1>
                <p>Manage games for {{ $ladder->name }}</p>
                        
                <a href="/admin/" class="btn btn-transparent btn-lg">
                    <i class="fa fa-chevron-left fa-lg fa-fw" aria-hidden="true"></i> Back to Admin
                </a>
            </div>
        </div>
    </div>
</div>
@endsection


@section('content')
<?php
    $numOfCols = 3;
    $rowCount = 0;
    $bootstrapColWidth = 12 / $numOfCols;
?>
<section class="light-texture game-detail supported-games">
    <div class="container">
        <div class="feature">
            <h3>Manage Games for {{ $ladder->name }} </h3>
            <div class="row">
            @foreach($games->get() as $game)
                <div class="col-md-{{ $bootstrapColWidth }}">
                    @include("admin._manage-game")
                </div>

                <?php $rowCount++; ?>
                @if($rowCount % $numOfCols == 0)
                </div><div class="row">
                @endif
            @endforeach
            </div>
        </div>
    </div>
</section>
@endsection




