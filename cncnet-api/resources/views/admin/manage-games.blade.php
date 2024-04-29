@extends('layouts.app')
@section('title', 'Ladder')

@section('feature-image', '/images/feature/feature-index.jpg')
@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Admin</span>
                    </h1>
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
                    @foreach ($games->get() as $game)
                        <div class="col-md-{{ $bootstrapColWidth }}">
                            @include('admin._manage-game')
                        </div>

                        <?php $rowCount++; ?>
                        @if ($rowCount % $numOfCols == 0)
                </div>
                <div class="row">
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
