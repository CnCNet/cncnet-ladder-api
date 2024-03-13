@extends('layouts.app')
@section('head')
    <script src="/js/chart.min.js"></script>
    <script src="/js/chartjs-adapter-date-fns.bundle.min.js"></script>
@endsection

@section('title', 'Viewing - ' . $ladderPlayer->username)
@section('body-class', 'body-player-detail')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev($history->ladder->abbreviation))

@section('feature')

    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img
                            src="{{ \App\Models\URLHelper::getLadderLogoByAbbrev($history->ladder->abbreviation) }}"
                            alt="{{ $history->ladder->name }}"
                            class="d-block img-fluid me-lg-0 ms-lg-auto"
                    />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold"> {{ $ladderPlayer->username }}</strong> <br/>
                        <span>{{ $history->ladder->name }}</span>
                    </h1>

                    <p class="lead text-uppercase">
                        <small>{{ $history->starts->format('F Y') }} -
                            @if ($history->ladder->clans_allowed)
                                <strong>Clan Ranked Match</strong>
                            @else
                                <strong>1 vs 1 Ranked Match</strong>
                            @endif
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
                            <a href="{{ \App\Models\URLHelper::getLadderUrl($history) }}">
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
                    <a href="{{ \App\Models\URLHelper::getLadderUrl($history) }}">
                        <span class="material-symbols-outlined icon pe-3">
                            military_tech
                        </span>
                        {{ $history->ladder->name }}
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <a href="">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        Viewing {{ $ladderPlayer->username }}
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
                        @include('components.avatar', [
                            'avatar' => $userPlayer->getUserAvatar(),
                            'size' => 150,
                            'type' => $history->ladder->clans_allowed ? 'clan' : 'player',
                        ])
                    </div>
                    <div class="player-rank pt-3 me-md-5">
                        <h1 class="username">
                            {{ $ladderPlayer->username }}
                            @if($playerOfTheDayAward)
                                {{ \App\Helpers\SiteHelper::getEmojiByMonth() }}
                            @endif
                        </h1>

                        @if ($history->ladder->clans_allowed)
                            <h2 class="rank text-uppercase mt-0">Clan {{ $player->clanPlayer->clan->short }}</h2>
                        @endif

                        <h3 class="rank highlight text-uppercase mt-0">Rank #{{ $ladderPlayer->rank }}</h3>
                        <div class="font-secondary-bold mb-1">
                            <span class="me-2">{!! \App\Helpers\LeagueHelper::getLeagueIconByTier($userTier) !!}</span>
                            {{ \App\Helpers\LeagueHelper::getLeagueNameByTier($userTier) }}
                        </div>
                        <div>
                            <span class="font-secondary-bold">Last online:</span>
                            <span class="font-secondary">{{ $ladderPlayer->last_active ?? 'Unknown' }}</span>
                        </div>
                        @if ($userPlayer->userSettings->getIsAnonymous() == false)
                            <div>
                                <span class="font-secondary-bold">User joined:</span>
                                <span class="font-secondary">{{ $userPlayer->userSince() }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($userIsMod)
                        <div class="mt-2 mb-2">
                            @include('ladders._modal-edit-player-name')
                            <button type="button" class="btn btn-secondary btn-sm" id="editPlayerName"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editPlayerName"> Edit Player Name
                            </button>
                        </div>
                    @endif

                    <div class="player-social pt-4 me-5">
                        @if ($userPlayer->getTwitchProfile())
                            <a href="{{ $userPlayer->getTwitchProfile() }}">
                                <i class="bi bi-twitch"></i>
                            </a>
                        @endif
                        @if ($userPlayer->getYouTubeProfile())
                            <a href="{{ $userPlayer->getYouTubeProfile() }}">
                                <i class="bi bi-youtube"></i>
                            </a>
                        @endif
                    </div>
                    @if ($playerOfTheDayAward)
                        <div class="pt-4 ms-auto ml-auto">
                            @include('ladders.player._award-player-of-the-day', [
                                'wins' => $playerOfTheDayAward->wins,
                                'username' => $playerOfTheDayAward->name,
                            ])
                        </div>
                    @endif
                </div>

                <div class="player-stats">
                    <div class="player-overall-stats grid">
                        <div class="column">
                            <h5 class="stat-title">Player stats</h5>
                            <div class="player-stats-drilldown stats-wrap">
                                <div class="stat-item">
                                    <span class="name">Points:</span> {{ $ladderPlayer->points }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Games:</span>{{ $ladderPlayer->game_count }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Wins:</span> {{ $ladderPlayer->games_won }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Losses:</span> {{ $ladderPlayer->games_lost }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Average FPS:</span> {{ $ladderPlayer->average_fps }}
                                </div>
                                <div class="stat-item">
                                    <span class="name">Played today:</span> {{ $playerGamesLast24Hours }}
                                </div>

                                @if ($userPlayer->userSettings->getIsAnonymous() == false)
                                    <div class="stat-item">
                                        <span class="name">Elo:</span> {{ $ladderPlayer->elo->elo ?? '' }}
                                    </div>
                                    <div class="stat-item">
                                        <span class="name">Elo Rank:</span> {{ $ladderPlayer->elo->rank ?? '' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="column">
                            <h5 class="stat-title">Top factions played</h5>
                            <div>
                                @include('ladders.player._player-factions')
                            </div>
                        </div>

                        <div class="column">
                            <h5 class="stat-title">Played this month</h5>
                            <div>
                                @include('ladders.player._player-chart')
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toggle achievements on for Blitz ladder only, TODO remove -->
                @if ($history->ladder->id == 8 || $history->ladder->id == 1)
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
                                     aria-valuenow="{{ $achievementsCount['percentage'] }}" aria-valuemin="0"
                                     aria-valuemax="100"
                                     style="width: {{ $achievementsCount['percentage'] }}%">
                                </div>
                            </div>
                            <small class="ms-1">{{ $achievementsCount['unlockedCount'] }}
                                /{{ $achievementsCount['totalToUnlock'] }}
                                unlocked</small>
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
                            <a href="{{ \App\Models\URLHelper::getPlayerProfileAchievementsUrl($history, $ladderPlayer->username) }}"
                               title="View all achievements" class="btn btn-outline btn-size-md">
                                View All Achievements
                            </a>
                        </div>
                    </div>
                @endif
                @include('ladders.player._player-admin-tools')
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
                            @include('ladders.player._games-table', ['player' => $ladderPlayer, 'games' => $games])
                            <div class="mt-2">
                                @include('components.pagination.paginate', ['paginator' => $games->appends(request()->query())])
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="player-vs-player pt-5 pb-5">
                <div class="container">
                    <div class="row">
                        @include('ladders.player._player-vs-player-matchups')
                    </div>
                </div>
            </section>
            <section class="player-maps pt-5 pb-5">
                <div class="container">
                    <div class="row">
                        @include('ladders.player._player-map-stats')
                    </div>
                </div>
            </section>
        </section>
    </div>

@endsection
