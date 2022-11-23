@extends('layouts.app')
@php $metaTitle = "Viewing Game - ";@endphp
@foreach ($playerGameReports as $k => $pgr)
    @php $player = $pgr->player()->first(); @endphp
    @php
        if ($k == 1) {
            $metaTitle .= ' vs ';
        }
    @endphp
    @php $metaTitle .= "$player->username"; @endphp;
@endforeach

@section('title', $metaTitle)
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev($history->ladder->abbreviation))

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
                    </h1>

                    <p class="lead text-uppercase">
                        @foreach ($playerGameReports as $k => $pgr)
                            <?php $gameStats = $pgr->stats; ?>
                            <?php $player = $pgr->player()->first(); ?>

                            <span>{{ $player->username }}</span>
                            @if ($k == 0)
                                <span><strong>VS</strong></span>
                            @endif
                        @endforeach
                    </p>
                    <p class="text-uppercase">
                        {{ $history->starts->format('F Y') }} - <strong>1 vs 1 Ranked Match</strong>
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
                <li class="breadcrumb-item active">
                    <a href="">
                        <span class="material-symbols-outlined pe-3">
                            swords
                        </span>
                        Viewing Game
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="game-detail">
        @php
            $gameAbbreviation = $history->ladder()->first()->abbreviation;
            $map = \App\Map::where('hash', '=', $game->hash)->first();
        @endphp

        @if (\Auth::user() && \Auth::user()->isAdmin())
            @include('ladders.components.game.admin-game-tools')
        @endif

        <div class="container">
            <section class="game-players mt-5">
                @foreach ($playerGameReports as $k => $pgr)
                    @php $gameStats = $pgr->stats; @endphp
                    @php $player = $pgr->player()->first(); @endphp
                    @php $playerCache = $player->playerCache($history->id);@endphp
                    @php $rank = $playerCache ? $playerCache->rank() : 0; @endphp
                    @php $points = $playerCache ? $playerCache->points : 0;@endphp

                    <div class="player-card {{ $k == 1 ? 'player-card-right' : '' }}">
                        <div class="player-avatar">
                            @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 150])
                        </div>

                        <div class="player-details">
                            <h2 class="username">
                                {{ $player->username }}
                            </h2>

                            <h5 class="rank pb-1">
                                Rank #{{ $rank }}
                            </h5>

                            <div class="d-flex">
                                <div class="faction">
                                    @if ($pgr->stats)
                                        @php $playerStats2 = \App\Stats2::where("id", $pgr->stats->id)->first(); @endphp
                                        @php $playerCountry = $playerStats2->faction($history->ladder->game, $pgr->stats->cty); @endphp
                                        <div class="player-faction player-faction-{{ $playerCountry }}"></div>
                                    @endif
                                </div>
                                <div class="points {{ $pgr->won ? 'won' : 'lost' }}">
                                    @if ($pgr->points >= 0)
                                        <span>{{ '+' }}</span>
                                    @endif
                                    {{ $pgr->points }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($k == 0)
                        <div class="player-vs d-flex align-items-center">
                            <h1>Vs</h1>
                        </div>
                    @endif
                @endforeach
            </section>
        </div>

        <div class="match-details text-center mt-5">
            <h4>{{ $game->scen }}</h4>
            <p>
                Duration: {{ gmdate('H:i:s', $gameReport->duration) }}
                <br />
                FPS: {{ $gameReport->fps }}
            </p>
        </div>

        @include('ladders.components.game.map-with-players', ['map' => $map])

        <section class="game {{ $gameAbbreviation }} mt-5 mb-5">
            <div class="container">
                <div class="stats-breakdown">
                    @foreach ($playerGameReports as $k => $pgr)
                        @php $gameStats = $pgr->stats; @endphp
                        @php $player = $pgr->player()->first(); @endphp

                        @if ($gameStats !== null)
                            @php $last_heap = 'Z'; @endphp

                            <div class="stats">
                                <div>
                                    <h2 class="username">
                                        {{ $player->username }}
                                    </h2>
                                </div>

                                @foreach ($heaps as $heap)
                                    <div>
                                        <div class="cameo-row">
                                            <div class="cameo-title">
                                                <h4>{{ $heap->description }}</h4>
                                            </div>
                                            <div class="cameo-body">
                                                @foreach ($gameStats->gameObjectCounts as $goc)
                                                    @if ($goc->countableGameObject->heap_name == $heap->name && $goc->countableGameObject->cameo != '')
                                                        <div class="{{ $gameAbbreviation }}-cameo cameo-tile cameo-{{ $goc->countableGameObject->cameo }}"><span
                                                                class="number">{{ $goc->count }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <?php $last_heap = substr($heap->name, 2, 1); ?>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endsection

    @if ($history->ends > Carbon\Carbon::now())
        @include('components.countdown', ['target' => $history->ends->toISO8601String()])
    @endif

    @section('js')
        <script>
            const triggerTabList = document.querySelectorAll('#myTab button')
            triggerTabList.forEach(triggerEl => {
                const tabTrigger = new bootstrap.Tab(triggerEl)

                triggerEl.addEventListener('click', event => {
                    event.preventDefault()
                    tabTrigger.show()
                })
            })
        </script>
    @endsection
