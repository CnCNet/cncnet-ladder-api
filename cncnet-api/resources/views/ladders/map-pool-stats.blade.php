@extends('layouts.app')
@section('title', 'Map Pool Statistics - ' . $history->ladder->name)
@section('page-body-class', $history->ladder->abbreviation)

@section('feature')
    <x-hero-split>
        <x-slot name="subpage">true</x-slot>
        <x-slot name="video">{{ \App\Models\URLHelper::getVideoUrlbyAbbrev($history->ladder->abbreviation) }}</x-slot>
        <x-slot name="title">
            <strong class="fw-bold">{{ $history->ladder->name }}</strong> <br />
            <span>Map Pool Statistics</span>
        </x-slot>

        <x-slot name="description">
            Games played per map for <strong>{{ $period['month_name'] }} {{ $period['year'] }}</strong>
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
                        <span class="material-symbols-outlined">home</span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('ladder.map-stats', ['date' => $history->short, 'game' => $history->ladder->abbreviation]) }}">
                        <span class="material-symbols-outlined icon pe-3">map</span>
                        {{ $history->ladder->name }}
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    Map Pool Statistics
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4 pb-5">
        <div class="container-xl">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="text-white mb-0">Statistics Overview</h3>
                        <div class="month-selector">
                            <label for="monthSelect" class="text-muted me-2">Select Month:</label>
                            <select id="monthSelect" class="form-select form-select-sm d-inline-block w-auto">
                                @foreach ($availableMonths as $monthOption)
                                    <option value="{{ $monthOption['short'] }}"
                                            @if ($monthOption['month'] == $period['month'] && $monthOption['year'] == $period['year']) selected @endif>
                                        {{ $monthOption['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-dark text-white mb-3">
                        <div class="card-body text-center">
                            <h3 class="display-4 mb-0">{{ $totalGames }}</h3>
                            <p class="text-muted mb-0">Total Games</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-dark text-white mb-3">
                        <div class="card-body text-center">
                            <h3 class="display-4 mb-0">{{ count($mapStats) }}</h3>
                            <p class="text-muted mb-0">Maps in Pool</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-dark text-white mb-3">
                        <div class="card-body text-center">
                            <h3 class="display-4 mb-0">{{ count(array_filter($mapStats, fn($s) => $s['game_count'] > 0)) }}</h3>
                            <p class="text-muted mb-0">Maps Played</p>
                        </div>
                    </div>
                </div>
            </div>

            @if (count($statsByTier) > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="text-white mb-3">Games by Map Tier</h4>
                    </div>
                    @foreach ($statsByTier as $tierNumber => $tierData)
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card bg-secondary text-white h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-uppercase text-muted mb-2">Tier {{ $tierNumber }}</h6>
                                    <h5 class="mb-2">{{ $tierData['tier_info']->name }}</h5>
                                    <h3 class="display-6 mb-2 text-primary">{{ $tierData['total_games'] }}</h3>
                                    <p class="mb-0 text-muted small">
                                        {{ $tierData['percentage'] }}% of total
                                        <br>
                                        {{ count($tierData['maps']) }} maps
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <ul class="nav nav-tabs mb-4" id="tierTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-maps" type="button" role="tab">
                        All Maps
                    </button>
                </li>
                @foreach ($statsByTier as $tierNumber => $tierData)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tier{{ $tierNumber }}-tab" data-bs-toggle="tab" data-bs-target="#tier{{ $tierNumber }}" type="button" role="tab">
                            Tier {{ $tierNumber }}: {{ $tierData['tier_info']->name }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content" id="tierTabContent">
                <div class="tab-pane fade show active" id="all-maps" role="tabpanel">

                    <div class="row">
                        @foreach ($mapStats as $stat)
                    @php
                        $qmMap = $stat['qm_map'];
                        $map = $stat['map'];
                        $gameCount = $stat['game_count'];
                        $percentage = $stat['percentage'];
                        $noGamesClass = $gameCount == 0 ? 'opacity-50' : '';
                        $imageHash = $map->image_hash ?: $map->hash;
                        $imageUrl = "https://ladder.cncnet.org/images/maps/{$ladder->game}/{$imageHash}.png";
                    @endphp

                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card bg-dark text-white h-100 {{ $noGamesClass }}">
                            <img src="{{ $imageUrl }}"
                                 class="card-img-top"
                                 alt="{{ $map->name }}"
                                 style="height: 200px; object-fit: cover; background: #2c3e50;"
                                 loading="lazy"
                                 onerror="this.style.display='none'">
                            <div class="card-body">
                                <h5 class="card-title">{{ $map->name }}</h5>
                                @if ($qmMap->description)
                                    <p class="card-text text-muted small fst-italic">{{ $qmMap->description }}</p>
                                @endif

                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-secondary">
                                    <div>
                                        <h3 class="mb-0 {{ $gameCount > 0 ? 'text-primary' : 'text-muted' }}">{{ $gameCount }}</h3>
                                        <small class="text-muted text-uppercase">Games</small>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-0 {{ $gameCount > 0 ? 'text-success' : 'text-muted' }}">{{ $percentage }}%</h3>
                                        <small class="text-muted text-uppercase">Share</small>
                                    </div>
                                </div>

                                @if ($totalGames > 0)
                                    <div class="progress mt-3" style="height: 8px;">
                                        <div class="progress-bar bg-gradient"
                                             role="progressbar"
                                             style="width: {{ min($percentage, 100) }}%;"
                                             aria-valuenow="{{ $percentage }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        </div>
                    @endforeach
                    </div>
                </div>

                @foreach ($statsByTier as $tierNumber => $tierData)
                    <div class="tab-pane fade" id="tier{{ $tierNumber }}" role="tabpanel">
                        <div class="row">
                            @foreach ($tierData['maps'] as $stat)
                                @php
                                    $qmMap = $stat['qm_map'];
                                    $map = $stat['map'];
                                    $gameCount = $stat['game_count'];
                                    $percentage = $stat['percentage'];
                                    $noGamesClass = $gameCount == 0 ? 'opacity-50' : '';
                                    $imageHash = $map->image_hash ?: $map->hash;
                                    $imageUrl = "https://ladder.cncnet.org/images/maps/{$ladder->game}/{$imageHash}.png";
                                @endphp

                                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                    <div class="card bg-dark text-white h-100 {{ $noGamesClass }}">
                                        <img src="{{ $imageUrl }}"
                                             class="card-img-top"
                                             alt="{{ $map->name }}"
                                             style="height: 200px; object-fit: cover; background: #2c3e50;"
                                             loading="lazy"
                                             onerror="this.style.display='none'">
                                        <div class="card-body">
                                            <h5 class="card-title">{{ $map->name }}</h5>
                                            @if ($qmMap->description)
                                                <p class="card-text text-muted small fst-italic">{{ $qmMap->description }}</p>
                                            @endif

                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-secondary">
                                                <div>
                                                    <h3 class="mb-0 {{ $gameCount > 0 ? 'text-primary' : 'text-muted' }}">{{ $gameCount }}</h3>
                                                    <small class="text-muted text-uppercase">Games</small>
                                                </div>
                                                <div class="text-end">
                                                    <h3 class="mb-0 {{ $gameCount > 0 ? 'text-success' : 'text-muted' }}">{{ $percentage }}%</h3>
                                                    <small class="text-muted text-uppercase">Share</small>
                                                </div>
                                            </div>

                                            @if ($totalGames > 0)
                                                <div class="progress mt-3" style="height: 8px;">
                                                    <div class="progress-bar bg-gradient"
                                                         role="progressbar"
                                                         style="width: {{ min($percentage, 100) }}%;"
                                                         aria-valuenow="{{ $percentage }}"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            @if (count($mapStats) == 0)
                <div class="alert alert-info" role="alert">
                    <h4 class="alert-heading">No Map Data</h4>
                    <p>No maps found in the map pool for this ladder.</p>
                </div>
            @endif

            @if ($totalGames == 0)
                <div class="alert alert-warning" role="alert">
                    <h4 class="alert-heading">No Games Played</h4>
                    <p>No games were played during {{ $period['month_name'] }} {{ $period['year'] }}.</p>
                </div>
            @endif
        </div>
    </section>
@endsection

@section('css')
    <style>
        .progress-bar {
            background: linear-gradient(90deg, #3498db 0%, #2ecc71 100%);
        }
        .nav-tabs {
            border-bottom: 2px solid #495057;
        }
        .nav-tabs .nav-link {
            color: #adb5bd;
            background-color: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 0.75rem 1.25rem;
        }
        .nav-tabs .nav-link:hover {
            color: #fff;
            border-bottom-color: #495057;
        }
        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: transparent;
            border-bottom-color: #3498db;
        }
        .month-selector {
            display: flex;
            align-items: center;
        }
    </style>
@endsection

@section('js')
    <script>
        document.getElementById('monthSelect').addEventListener('change', function() {
            const selectedMonth = this.value;
            const ladderAbbrev = '{{ $ladder->abbreviation }}';
            window.location.href = `/ladder/${selectedMonth}/${ladderAbbrev}/map-stats`;
        });
    </script>
@endsection
