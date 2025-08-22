@extends('layouts.app')
@section('title', $history->ladder->name)
@section('page-body-class', $history->ladder->abbreviation)

@section('feature')
    <x-hero-split>
        <x-slot name="subpage">true</x-slot>
        <x-slot name="video">{{ \App\Models\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation) }}</x-slot>
        <x-slot name="title">
            <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
            <span>Ladder Games</span>
        </x-slot>

        <x-slot name="description">
            Compete in <strong>1vs1</strong> or <strong>2vs2</strong> ranked matches with players all over the world.
        </x-slot>

        <x-slot name="logo">
            <img src="{{ \App\Models\URLHelper::getLadderLogoByAbbrev($history->ladder->abbreviation) }}" alt="{{ $history->ladder->name }}" />
        </x-slot>
    </x-hero-split>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ \App\Models\URLHelper::getLadderUrl($history) }}">
                        <span class="material-symbols-outlined icon pe-3">
                            military_tech
                        </span>
                        Ladders
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="">
                        <span class="material-symbols-outlined icon pe-3">
                            insights
                        </span>
                        Games
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section>
        <div class="container-xl">
            @if ($userIsMod && ($errorGames === null || $errorGames === false))
                <div class="mb-4 mt-4">
                    <a href="{{ \App\Models\URLHelper::getLadderUrl($history) . '/games?errorGames=true' }}" class="btn btn-danger btn-md">
                        View 0:03 Games
                    </a>
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-2 mt-4">
                        @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
                    </div>

                    @include('ladders.listing._games-table', ['games' => $games])
                    
                    <div class="mt-2">
                        @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
