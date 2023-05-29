@extends('layouts.app')
@section('head')
    <script src="/js/chart.min.js"></script>
    <script src="/js/chartjs-adapter-date-fns.bundle.min.js"></script>
@endsection

@section('title', 'Viewing - ' . $clanCache->clan_name)
@section('body-class', 'body-player-detail')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev($history->ladder->abbreviation))

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="{{ \App\URLHelper::getLadderLogoByAbbrev($history->ladder->abbreviation) }}" alt="{{ $history->ladder->name }}"
                        class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold"> {{ $clanCache->clan_name }}</strong> <br />
                        <span>{{ $history->ladder->name }}</span>
                    </h1>

                    <p class="lead text-uppercase">
                        <small>{{ $history->starts->format('F Y') }} -
                            <strong>Clan Ranked Match</strong>
                        </small>
                    </p>

                    <div class="mini-breadcrumb d-none d-lg-flex">
                        <div class="mini-breadcrumb-item">
                            <a href="/" class="">
                                <span class="material-symbols-outlined">
                                    home
                                </span>
                            </a>
                        </div>
                        <div class="mini-breadcrumb-item">
                            <a href="{{ \App\URLHelper::getLadderUrl($history) }}">
                                <span class="material-symbols-outlined icon">
                                    military_tech
                                </span>
                                {{ $history->ladder->name }}
                            </a>
                        </div>
                    </div>
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
                    <a href="/ladder">
                        <span class="material-symbols-outlined icon pe-3">
                            military_tech
                        </span>
                        Ladders
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ \App\URLHelper::getLadderUrl($history) }}">
                        <i class="bi bi-flag-fill pe-3"></i>
                        {{ $history->ladder->name }}
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <a href="">
                        <i class="bi bi-flag-fill pe-3"></i>

                        Viewing {{ $clanCache->clan_name }}
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <div class="player-detail">
        <div class="container">
            <section class="player-header">
                <div class="player-profile">
                    <div class="player-avatar me-5">
                        @include('components.avatar', ['avatar' => $clanCache->getClanAvatar(), 'size' => 150, 'type' => 'clan'])
                    </div>
                    <div class="player-rank pt-3 me-5">
                        <h1 class="username">{{ $clanCache->clan_name }}</h1>
                        <h3 class="rank highlight text-uppercase mt-0">Rank #{{ $clanCache->rank() }}</h3>
                    </div>

                    @if ($userIsMod)
                        {{-- <div>
                            @include('ladders._modal-edit-player-name')
                            <button type="button" class="btn btn-secondary btn-sm" id="editPlayerName" data-bs-toggle="modal"
                                data-bs-target="#editPlayerName"> Edit Player Name </button>
                        </div> --}}
                    @endif

                    <div class="player-social pt-4 me-5">
                        {{-- @if ($userPlayer->getTwitchProfile())
                            <a href="{{ $userPlayer->getTwitchProfile() }}">
                                <i class="bi bi-twitch"></i>
                            </a>
                        @endif
                        @if ($userPlayer->getYouTubeProfile())
                            <a href="{{ $userPlayer->getYouTubeProfile() }}">
                                <i class="bi bi-youtube"></i>
                            </a>
                        @endif --}}
                    </div>
                    {{-- @if ($playerOfTheDayAward)
                        <div class="pt-4 ms-auto ml-auto">
                            @include('ladders.player._award-player-of-the-day', [
                                'wins' => $playerOfTheDayAward->wins,
                                'username' => $playerOfTheDayAward->username,
                            ])
                        </div>
                    @endif --}}
                </div>

                <div class="player-stats">
                    <div class="player-overall-stats grid">
                        <div class="column">
                            <h5 class="stat-title"><i class="bi bi-flag-fill icon-clan pe-3"></i> Clan stats</h5>
                            <div class="player-stats-drilldown stats-wrap">
                                <div class="stat-item">
                                    <span class="name">Points:</span> {{ $clanCache->points }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Games:</span>{{ $clanCache->games }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Wins:</span> {{ $clanCache->wins }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Losses:</span> {{ $clanCache->games - $clanCache->wins }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Average FPS:</span> {{ $clanCache->fps }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Played today:</span> {{ $clanGamesLast24Hours }}
                                </div>
                            </div>

                            @if ($clanCache->clan->description)
                                <div class="mt-5">
                                    <h5 class="stat-title"> Clan Bio</h5>
                                    <div class="player-stats-drilldown stats-wrap">
                                        {{ $clanCache->clan->description }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="column">
                            <h5 class="stat-title"><i class="bi bi-graph-up pe-3"></i> Clan players performance</h5>

                            <div class="clan-player-breakdown stats-wrap">
                                <div class="table-responsive" style="width:100%">
                                    <table class="table table player-factions-table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Player</th>
                                                <th scope="col">Wins</th>
                                                <th scope="col">Lost</th>
                                                <th scope="col">Played</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($clanPlayerWinLossByMonth as $playerId => $report)
                                                <tr>
                                                    <td>
                                                        @foreach ($clanPlayers as $clanPlayer)
                                                            @php $player = $clanPlayer->player; @endphp

                                                            @if ($playerId == $player->id)
                                                                {{ $player->username }}
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td class="count won">{{ $report['wins'] }}</td>
                                                    <td class="count lost">{{ $report['losses'] }}</td>
                                                    <td>{{ $report['total'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="column">
                            <h5 class="stat-title"><i class="bi bi-calendar-date pe-3"></i> Played this month</h5>
                            <div>
                                @include('ladders.player._player-chart')
                            </div>
                        </div>
                    </div>
                </div>

                <div class="column mt-5 mt-lg-0">
                    <h5 class="stat-title">Clan members</h5>
                    <div>
                        @foreach ($clanPlayers as $clanPlayer)
                            @php $player = $clanPlayer->player; @endphp
                            <div>
                                <a class="" href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}"
                                    title="Go to {{ $player->username }}'s profile">
                                    {{ $player->username }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>



                <!-- Toggle achievements on for Blitz ladder only, TODO remove -->
                {{-- @if ($history->ladder->id == 8 || $history->ladder->id == 1)
                    <div class="player-achievements">
                        <div class="d-flex align-items-center">
                            <h5 class="d-flex align-items-center">
                                <span class="material-symbols-outlined icon pe-2">
                                    stars
                                </span>
                                <strong class="pe-1">Recent Achievements</strong>
                            </h5>
                        </div>

                        <div class="ms-1 me-3 d-flex align-items-center mb-2">
                            <div class="achievement-progress progress" style="width:150px;">
                                <div class="progress-bar" role="progressbar" aria-label="Default striped example"
                                    aria-valuenow="{{ $achievementsCount['percentage'] }}" aria-valuemin="0" aria-valuemax="100"
                                    style="width: {{ $achievementsCount['percentage'] }}%">
                                </div>
                            </div>
                            <small class="ms-1">{{ $achievementsCount['unlockedCount'] }}/{{ $achievementsCount['totalToUnlock'] }} unlocked</small>
                        </div>

                        <div class="d-flex flex-wrap">
                            @foreach ($achievements as $achievement)
                                @include('ladders.components._achievement-tile', [
                                    'cameo' => $achievement->cameo,
                                    'name' => $achievement->achievement_name,
                                    'description' => $achievement->achievement_description,
                                    'unlocked' => true,
                                    'unlockedDate' => $achievement->achievement_unlocked_date,
                                    'abbreviation' => $history->ladder->abbreviation,
                                    'tag' => $achievement->tag,
                                ])
                            @endforeach
                        </div>

                        <div class="ms-2 mt-2 mb-2">
                            <a href="{{ \App\URLHelper::getPlayerProfileAchievementsUrl($history, $ladderPlayer->username) }}"
                                title="View all achievements" class="btn btn-outline btn-size-md">
                                View All Achievements
                            </a>
                        </div>
                    </div>
                @endif --}}
                {{-- @include('ladders.player._player-admin-tools') --}}
            </section>
        </div>

        <section>
            <section class="mt-5">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-2">
                                @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
                            </div>

                            @include('ladders.clan._games-table', ['clan' => $clanCache, 'games' => $games])

                            <div class="mt-2">
                                @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- <section class="player-vs-player pt-5 pb-5">
                <div class="container">
                    <div class="row">
                        @include('ladders.player._player-vs-player-matchups')
                    </div>
                </div>
            </section> --}}

            {{-- <section class="player-maps pt-5 pb-5">
                <div class="container">
                    <div class="row">
                        @include('ladders.player._player-map-stats')
                    </div>
                </div>
            </section> --}}
        </section>
    </div>

@endsection
