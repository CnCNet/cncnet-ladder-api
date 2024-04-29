@extends('layouts.app')
@section('head')
    <script src="/js/chart.min.js"></script>
    <script src="/js/chartjs-adapter-date-fns.bundle.min.js"></script>
@endsection

@section('title', 'Viewing - ' . $ladderPlayer->username)
@section('body-class', 'body-player-detail')
@section('page-body-class', $history->ladder->abbreviation)

@section('feature')
    <x-hero-split>
        <x-slot name="subpage">true</x-slot>
        <x-slot name="video">{{ \App\Models\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation) }}</x-slot>
        <x-slot name="title">
            <strong class="fw-bold"> {{ $ladderPlayer->username }}</strong> <br />
            <span>Achievements</span>
        </x-slot>

        <x-slot name="description">
            @if ($history->ladder->clans_allowed)
                {{ $history->starts->format('F Y') }} - <strong>Clan Ranked Match</strong>
            @else
                {{ $history->starts->format('F Y') }} - <strong>Ranked Match</strong>
            @endif

            <br />

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
                <li class="breadcrumb-item">
                    <a href="{{ \App\Models\URLHelper::getPlayerProfileUrl($history, $ladderPlayer->username) }}">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        {{ $ladderPlayer->username }}
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <a href="">
                        <span class="material-symbols-outlined icon pe-3">
                            stars
                        </span>
                        Achievements
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <div class="player-detail">
        <div class="container">
            <div class="player-profile">
                <div class="player-avatar me-5">
                    @include('components.avatar', ['avatar' => $userPlayer->getUserAvatar(), 'size' => 150])
                </div>
                <div class="player-rank pt-3 me-5">
                    <h1 class="username">{{ $ladderPlayer->username }}</h1>
                    <h3 class="rank highlight text-uppercase mt-0">Rank #{{ $ladderPlayer->rank }}</h3>
                </div>
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
            </div>

            @foreach ($achievements as $tag => $achievement)
                <h5 class="stat-title mt-5 mb-3">{{ $tag }}</h5>
                <div class="achievement-container">
                    @foreach ($achievement as $achievementArr)
                        <div class="achievement-row">
                            @php
                                $a = $achievementArr['achievement'];
                                $unlocked = $achievementArr['unlocked'];
                                $unlockedProgress = $achievementArr['unlockedProgress'];
                            @endphp

                            @include('ladders.components._achievement-tile', [
                                'cameo' => $a->cameo,
                                'name' => $a->achievement_name,
                                'description' => $a->achievement_description,
                                'unlocked' => $unlocked,
                                'unlockedDate' => isset($unlocked) ? $unlocked->achievement_unlocked_date : null,
                                'unlockedProgress' => $unlockedProgress,
                                'abbreviation' => $history->ladder->abbreviation,
                                'tag' => $tag,
                            ])
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endsection
