@extends('layouts.app')
@section('title', $history->ladder->name . ' Ladder')
@section('feature-image', '/images/feature/feature-' . $history->ladder->abbreviation . '.jpg')

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="/images/games/{{ $history->ladder->abbreviation }}/logo.png" alt="{{ $history->ladder->name }}" class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
                        <span>Ladder Rankings</span>
                    </h1>
                    <p class="lead text-uppercase">
                        <small>{{ $history->starts->format('F Y') }} - <strong>1 vs 1 Ranked Match</strong></small>
                    </p>
                </div>
            </div>
        </div>
    </div>
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
                    <a href="{{ \App\URLHelper::getLadderUrl($history) }}">
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
        <div class="container">
            @if ($userIsMod && ($errorGames === null || $errorGames === false))
                <div class="mb-4">
                    <a href="{{ \App\URLHelper::getLadderUrl($history) . '?errorGames=true' }}" class="btn btn-danger btn-md">
                        View 0:03 Games
                    </a>
                </div>
            @endif

            @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
            @include('components.global-recent-games', ['games' => $games])
            @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
        </div>
    </section>
@endsection
