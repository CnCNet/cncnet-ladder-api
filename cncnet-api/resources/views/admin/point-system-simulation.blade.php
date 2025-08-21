@extends('layouts.app')
@section('title', 'Point System – Simulation')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev($ladder->abbreviation) }}">
        <x-slot name="title">Point System – Simulation</x-slot>
        <x-slot name="description">
            Recalculate ladder results with custom rules and compare to cached results.
        </x-slot>

        <div class="mini-breadcrumb d-none d-lg-flex">
            <div class="mini-breadcrumb-item">
                <a href="/" class=""><span class="material-symbols-outlined">home</span></a>
            </div>
            <div class="mini-breadcrumb-item">
                <a href="/admin" class=""><span class="material-symbols-outlined">admin_panel_settings</span></a>
            </div>
        </div>
    </x-hero-with-video>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/"><span class="material-symbols-outlined">home</span></a></li>
                <li class="breadcrumb-item"><a href="/admin"><span class="material-symbols-outlined pe-3">admin_panel_settings</span>Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Point System – Simulation</li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
<section class="pt-4 mt-5 mb-5">
    <div class="container">
        <h2 class="mb-4">Point System Simulation</h2>

        @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>There were problems with your input:</strong>
            <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
        @endif

        <form method="get" class="card mb-4">
        <div class="card-body">
            <hr>
            <h5 class="mb-4">Ladder / Time Frame</h5>
            <div class="row g-3 align-items-end mb-2">
                <div class="col-md-4">
                    <label for="abbreviation" class="form-label">Ladder</label>
                    <select id="abbreviation" name="abbreviation" class="form-select">
                    @foreach($ladders as $ladderOption)
                        <option value="{{ $ladderOption->abbreviation }}"
                        {{ $selectedAbbrev === $ladderOption->abbreviation ? 'selected' : '' }}>
                        {{ $ladderOption->name }}
                        </option>
                    @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="month" class="form-label">Month</label>
                    <select id="month" name="month" class="form-select">
                    @foreach($monthOptions as $opt)
                        <option value="{{ $opt['value'] }}" {{ $selectedMonth === $opt['value'] ? 'selected' : '' }}>
                        {{ $opt['label'] }}
                        </option>
                    @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="player_id" class="form-label">Details for player</label>
                    <select id="player_id" name="player_id" class="form-select">
                    <option value="0">–</option>
                    @foreach($playerOptions as $opt)
                        <option value="{{ $opt['id'] }}" {{ (int)$selectedPlayerId === (int)$opt['id'] ? 'selected' : '' }}>
                        {{ $opt['label'] }}
                        </option>
                    @endforeach
                    </select>
                </div>
            </div>

            <hr>
            <h5 class="mb-4">Point System Variables</h5>
            <div class="row g-3 align-items-start">
            <div class="col-md-2">
                <label class="form-label">wol_k</label>
                <input type="number" class="form-control" name="wol_k" value="{{ old('wol_k', $simParams['wol_k'] ?? '') }}">
                <div class="form-text">The Westwood Online (WOL) component. This is an ELO-like system that resets every month.</div>
                <!-- Default: {{ $defaults['wol_k'] ?? '–' }} -->
            </div>

            <div class="col-md-2">
                <label class="form-label">upset_k</label>
                <input type="number" class="form-control" name="upset_k" value="{{ old('upset_k', $simParams['upset_k'] ?? '') }}">
                <div class="form-text">Based on continuous CnCNet-ELO. Depending on win probability, the winner may gain up to <code>upset_k</code> bonus points.</div>
                <!-- Default: {{ $defaults['upset_k'] ?? '–' }} -->
            </div>

            <div class="col-md-3">
                <label class="form-label">upset_k_loser_multiplier</label>
                <input type="number" step="0.01" class="form-control" name="upset_k_loser_multiplier" value="{{ old('upseupset_k_loser_multipliert_k', $simParams['upset_k_loser_multiplier'] ?? '') }}">
                <div class="form-text">Factor applied to the loser’s penalty for the upset component (<code>upset_k</code> × <code>upset_k_loser_multiplier</code>).</div>
                <!-- Default: {{ $defaults['upset_k_loser_multiplier'] ?? '–' }} -->
            </div>

            <div class="col-md-2">
                <label class="form-label">fixed_points</label>
                <input type="number" class="form-control" name="fixed_points" value="{{ old('fixed_points', $simParams['fixed_points'] ?? '') }}">
                <div class="form-text">Fixed amount of points granted for each win or loss.</div>
                <!-- Default: {{ $defaults['fixed_points'] ?? '–' }} -->
            </div>

            <div class="col-md-2">
        <label class="form-label">no_negative_points</label>
        <select name="no_negative_points" class="form-select">
            <option value="1" {{ old('no_negative_points', (isset($simParams) && $simParams['no_negative_points']) ? 1 : 0) == 1 ? 'selected' : '' }}>on</option>
            <option value="0" {{ old('no_negative_points', (isset($simParams) && $simParams['no_negative_points']) ? 1 : 0) == 0 ? 'selected' : '' }}>off</option>
        </select>
        <div class="form-text">When turned on, ladder points cannot drop below zero. When turned off, players might end up with negativ points.</div>
        <!--  Default: {{ ($defaults['no_negative_points'] ?? false) ? 'on' : 'off' }} -->
        </div>
        <hr>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Recalculate</button>
            </div>
            </div>
        </div>
        </form>

        <div class="mb-3">
            <h5 class="mb-1">
                {{ $ladder ? $ladder->name : '–' }}
                — {{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->isoFormat('MMMM YYYY') }}
            </h5>
            @if($history)
                <div class="text-muted small">
                    LadderHistory ID: {{ $history->id }} |
                    Period: {{ $startOfMonth->toDateString() }} – {{ $endOfMonth->toDateString() }} |
                    Simulation executed in {{ number_format($duration, 3) }} seconds.
                </div>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-warning">
                No valid input parameters.
            </div>
        @elseif(!$history)
            <div class="alert alert-warning">
                No <code>ladder_history</code> period found for this ladder/month.
            </div>
        @elseif($compare->isEmpty())
            <div class="alert alert-info">
                No data found for this selection.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm table-compact align-middle w-auto" style="table-layout:auto; min-width: max-content;">
                    <thead class="thead-light">
                        <tr>
                            <th># Rank</th>
                            <th># New</th>
                            <th class="text-end">Δ Rank</th>
                            <th>Player (Alias)</th>
                            <th class="text-end">Points</th>
                            <th class="text-end">Points (New)</th>
                            <th class="text-end">Δ Points</th>
                            <th class="text-end">Games</th>
                            <th class="text-end">Games (New)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compare as $row)
                            @php
                                $dPoints = (int)$row['points_sim'] - (int)$row['points_old'];
                                $dRank   = (int)$row['delta_rank'];
                                $deltaPointsClass = $dPoints == 0 ? 'delta-zero' : ($dPoints > 0 ? 'delta-pos' : 'delta-neg');
                                $deltaRankClass   = $dRank == 0 ? 'delta-zero' : ($dRank < 0 ? 'delta-pos' : 'delta-neg');
                            @endphp
                            <tr>
                                <td>{{ $row['rank_old'] ?: '–' }}</td>
                                <td>{{ $row['rank_sim'] ?: '–' }}</td>
                                <td class="text-end">
                                    <span class="{{ $deltaRankClass }}">{{ $dRank > 0 ? '▼ ' : ($dRank < 0 ? '▲ ' : '') }}{{ abs($dRank) }}</span>
                                </td>
                                <td>{{ $row['display_name'] }}</td>
                                <td class="text-end">{{ number_format($row['points_old'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['points_sim'], 0, ',', '.') }}</td>
                                <td class="text-end">
                                    <span class="{{ $deltaPointsClass }}">
                                        {{ $dPoints > 0 ? '+' : '' }}{{ number_format($dPoints, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $row['games_old'] }}</td>
                                <td class="text-end">{{ $row['games_sim'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($selectedPlayerId && !empty($gameBreakdown))
                <hr class="my-4">
                <h5 class="mb-3">All games for {{ optional($playerOptions->firstWhere('id', (int)$selectedPlayerId))['label'] ?? 'Auswahl' }}</h5>

                @php
                    // Determine maximum number of participants.
                    $maxParticipants = 0;
                    foreach ($gameBreakdown as $g)
                    {
                        $c = isset($g['participants']) ? count($g['participants']) : 0;
                        if ($c > $maxParticipants)
                            $maxParticipants = $c;
                    }
                @endphp

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm table-compact align-middle w-auto" style="table-layout:auto; min-width: max-content;">
                    <thead class="thead-light">
                        <tr>
                        <th>Datum/Zeit</th>
                        <th>Game ID</th>
                        @for ($i = 1; $i <= $maxParticipants; $i++)
                            <th>Player {{ $i }}</th>
                            <th class="text-end">Points</th>
                            <th class="text-end">Points (New)</th>
                        @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gameBreakdown as $g)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($g['created_at'])->isoFormat('DD.MM.YYYY HH:mm') }}</td>
                            <td>{{ $g['game_id'] }}</td>
                            @php
                            $p = $g['participants'] ?? [];
                            @endphp
                            @for ($i=0; $i<$maxParticipants; $i++)
                            @if (isset($p[$i]))
                                <td>
                                @php
                                    $isDraw = $p[$i]['draw'] ?? false;
                                    $isWinner = !$isDraw && $g['winningTeam'] !== null && $p[$i]['team'] == $g['winningTeam'];
                                    $nameClass = $isDraw ? 'delta-zero' : ($isWinner ? 'delta-pos' : 'delta-neg');
                                @endphp
                                <span class="{{ $nameClass }}">{{ $p[$i]['name'] }}</span>
                                </td>
                                <td class="text-end">{{ number_format((int)$p[$i]['old_points'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int)$p[$i]['new_points'], 0, ',', '.') }}</td>
                            @else
                                <td></td>
                                <td></td>
                                <td></td>
                            @endif
                            @endfor
                        </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>
            @elseif($selectedPlayerId)
                <hr class="my-4">
                <div class="alert alert-info mb-0">
                    No games for the selected player.
                </div>
            @endif
        @endif
    </div>
</section>

<style>
  .delta-pos { color: #40E040; }
  .delta-neg { color: #FF6060; }
  .delta-zero { color: #666666; }
</style>
@endsection

