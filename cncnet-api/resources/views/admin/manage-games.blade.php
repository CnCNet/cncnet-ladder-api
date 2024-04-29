@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Manage Games</x-slot>
        <x-slot name="description">
            View and manage ladder games
        </x-slot>

        <div class="mini-breadcrumb d-none d-lg-flex">
            <div class="mini-breadcrumb-item">
                <a href="/" class="">
                    <span class="material-symbols-outlined">
                        home
                    </span>
                </a>
            </div>
            <div class="mini-breadcrumb-item">
                <a href="/admin" class="">
                    <span class="material-symbols-outlined">
                        admin_panel_settings
                    </span>
                </a>
            </div>
        </div>
    </x-hero-with-video>
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
