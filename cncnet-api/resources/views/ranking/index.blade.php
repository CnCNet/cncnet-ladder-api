@extends('layouts.app')
@section('title', 'Ladder Elo Ratings')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="{{ Vite::asset("$logoToUse") }}" alt="logo" class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3">
                        <strong class="fw-bold">Ladder</strong> <span>ELO Ratings</span>
                    </h1>

                    <p class="lead text-uppercase">
                        <small>Last updated - <strong>{{ $dateLastUpdated }} GMT</strong></small>
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
                <li class="breadcrumb-item active">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('css')
    <style>
        /* 1px header and general cell padding. */
        #ratings td,
        th {
            border: 1px solid #80A050;
            padding: 8px;
        }

        #ratings tr th {
            text-align: left;
            font-size: 18px;
            color: #b5c9e0;
            height: 40px;
            vertical-align: middle;
            /* top right bottom left */
            padding: 8px 8px 8px 8px;
            font-family: Helvetica, Arial, sans-serif;
            line-height: 40px;
        }

        #ratings tr td {
            text-align: left;
            font-size: 18px;
            color: #eeeeff;
            vertical-align: middle;
            /* top right bottom left */
            padding: 12px 8px 8px 8px;
            font-family: Helvetica, Arial, sans-serif;
        }

        #ratings tr td.mini {
            text-align: left;
            font-size: 12px;
            color: #ccccee;
            vertical-align: middle;
            /* top right bottom left */
            padding: 10px 8px 8px 8px;
            font-family: Helvetica, Arial, sans-serif;
        }

        #ratings tr td.inactive {
            color: #ff6060;
            font-size: 12px;
            font-weight: bold;
            text-align: left;
            vertical-align: middle;
        }

        #ratings tr td.active {
            color: #40E040;
            font-size: 12px;
            font-weight: bold;
            text-align: left;
            vertical-align: middle;
        }

        #ratings tr td.loser {
            color: #ff6060;
            text-align: left;
            vertical-align: middle;
            padding-right: 64px;
        }

        #ratings tr td.winner {
            color: #40E040;
            font-weight: bold;
            text-align: left;
            vertical-align: middle;
            padding-right: 64px;
        }

        /* Alternating background color for rows. */
        #ratings tr:nth-child(even) {
            background-color: #1f2331;
        }

        #ratings tr:nth-child(odd) {
            background-color: #13161f;
        }

        /* Background colors for the two header rows. */
        #ratings tr:nth-child(1) {
            background-color: #000000;
        }

        #ratings thead tr td:first-child,
        #ratings tbody tr th:first-child {
            width: 32px;
            min-width: 32px;
            max-width: 32px;
        }

        /* No for no faction header. */
        #ratings tbody tr:nth-child(1) {
            border: 0px;
        }

        /* Hover effect for selected row. */
        #ratings tr:hover:nth-child(n+2),
        #ratings tr:hover td:nth-child(n+2) {
            background-color: #408040;
            color: #ffffff;
        }

        #links table {
            margin-bottom: 20px;
            white-space: nowrap;
            table-layout: fixed;
            vertical-align: middle;
            border: 1px solid #808080;
        }

        #links tbody {
            border: 0px;
        }

        #links tr {
            display: flex;
            align-items: stretch;
        }

        #links td:last-child {
            flex: 1;
        }

        #links tr td:nth-child(1) {
            color: #cccccc;
            font-size: 16px;
            text-align: right;
            font-family: Helvetica, Arial, sans-serif;
            height: 0px;
        }

        .linknormal a {
            color: #808080;
            text-align: center;
            font-size: 16px;
            font-family: Helvetica, Arial, sans-serif;
            text-decoration: none;
            display: inline-block;
            border: 1px solid #808080;
            padding: 8px 8px 10px 10px;
        }

        .linknormal a:hover {
            color: #ffff00;
        }

        .linkactive a {
            color: #ffffff;
            text-align: center;
            font-size: 16px;
            font-family: Helvetica, Arial, sans-serif;
            text-decoration: none;
            display: inline-block;
            border: 1px solid #cccccc;
            background-color: #808080;
            padding: 8px;
        }

        .linknormal a:hover {
            color: #ffffff;
        }

        .tooltip {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 256px;
            background-color: black;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            border-width: 2px;
            border-style: solid;
            border-color: #00ff8a;
            padding: 5px;
            position: absolute;
            z-index: 1;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: transparent transparent black transparent;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .circle {
            margin-left: 5px;
            font-size: 18px;
            color: #80A080;
        }

        /* Prevent tooltip from being cut at table border. */
        table {
            overflow: visible;
        }

        td {
            position: relative;
            overflow: visible;
        }
    </style>
@endsection

@section('content')
    <div class="ladder-index">
        <section class="pt-5 pb-5">
            <div class="container">

                <div class="row" style="min-height: 1px; display: flex;">
                    <!-- Original Game Mode and Players Section -->
                    <div class="col-lg-6 d-flex flex-column justify-content-start">
                        <h5>Game mode</h5>
                        <div class="mb-5">
                            @foreach ($gameModes as $k => $gm)
                                <a href="?list={{ $index }}&mode={{ $gameModesShort[$k] }}"
                                   class="btn {{ $gameModesShort[$k] == $gameMode ? 'btn-primary' : 'btn-secondary' }}">{{ $gm }}</a>
                            @endforeach
                        </div>

                        <h5>Players</h5>
                        <div class="mb-5">
                            @foreach ($players as $k => $player)
                                <a href="?list={{ $k }}&mode={{ $gameMode }}"
                                   class="btn {{ $k == $index ? 'btn-primary' : 'btn-secondary' }}">{{ $player }}</a>
                            @endforeach
                        </div>

                        <h5>More stats</h5>
                        <div class="mb-5">
                            @foreach ($stats as $k => $stat)
                                <a href="?list={{ ($k + sizeof($players) + sizeof($upsets)) }}&mode={{ $gameMode }}"
                                   class="btn {{ ($k + sizeof($players) + sizeof($upsets)) == $index ? 'btn-primary' : 'btn-secondary' }}">{{ $stat }}</a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Duplicated Players Section -->
                    <div class="col-lg-6 d-flex flex-column justify-content-end">
                        <!-- Just Players section without the game mode -->
                        <h5>Upsets</h5>
                        <div class="mb-5">
                            @foreach ($upsets as $k => $upset)
                                <a href="?list={{ ($k + sizeof($players)) }}&mode={{ $gameMode }}"
                                   class="btn {{ ($k + sizeof($players)) == $index ? 'btn-primary' : 'btn-secondary' }}">{{ $upset }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div style="text-align:center;font-size:24px;font-weight: bold;padding:10px 10px 10px 10px">{{ $description }}</div>
                <div class="table-responsive">
                    <table id="ratings" style="border-collapse: collapse; width: auto; margin-left: auto; margin-right: auto;" class="table">
                        <tbody>
                            <tr>
                                @foreach ($columns as $value)
                                    @if (isset($value['info']))
                                        <th>{!! $value['header'] !!}
                                            <span class="tooltip">
                                                <span class="circle">&#x24D8;</span>
                                                <span class="tooltiptext">{{ $value['info'] }}</span>
                                            </span>
                                        </th>
                                    @elseif (str_ends_with($value['name'], '_elo') || str_ends_with($value['name'], '_deviation') || str_ends_with($value['name'], '_games'))
                                        <th style="horiz-align: right;">{{ $value['header'] }}
                                            <span>
                                                <img src=" {{ Vite::asset($factionImages[substr($value['name'], 0, 3)]) }}" height="32px" style="horiz-align: right;vertical-align: middle;">
                                            </span>
                                        </th>
                                    @else
                                        <th>{{ $value['header'] }}</th>
                                    @endif
                                @endforeach
                            </tr>

                            @foreach ($data as $value)
                                <tr>
                                    @foreach ($columns as $column)
                                        @if (!isset($value[$column['name']]))
                                            <td></td>
                                        @elseif (str_starts_with($column['name'], 'faction'))
                                            <td style="width:64px;padding:4px 1px 1px 12px;"><img src=" {{ Vite::asset($factionImages[$value[$column['name']]]) }}" height="32px"></td>
                                        @elseif ($column['name'] == 'loser')
                                            <td class="loser">{{ $value[$column['name']] }}</td>
                                        @elseif ($column['name'] == 'winner')
                                            <td class="winner">{{ $value[$column['name']] }}</td>
                                        @elseif ($column['name'] == 'status' && strtolower(str($value[$column['name']])) == 'inactive')
                                            <td class="inactive">{{ $value[$column['name']] }}</td>
                                        @elseif ($column['name'] == 'status' && strtolower(str($value[$column['name']])) == 'active')
                                            <td class="active">{{ $value[$column['name']] }}</td>
                                        @elseif (str_starts_with($column['name'], 'delta_'))
                                            @php
                                                $delta = $value[$column['name']];
                                                $direction = $delta > 0 ? '▲' : ($delta < 0 ? '▼' : '');
                                                $color = $delta > 0 ? '#40E040' : ($delta < 0 ? '#FF6060' : '#CCCCCC');
                                                $absValue = $delta != 0 ? abs($delta) : '';
                                            @endphp
                                            <td style="text-align: right; color: {{ $color }};">
                                                {!! $direction !!} {{ $absValue }}
                                            </td>
                                        @elseif (isset($value['elo']) && (is_numeric($value[$column['name']]) || str_contains($value[$column['name']], '±')) && (str_starts_with($column['name'], 'all_') || str_starts_with($column['name'], 'sov_') || str_starts_with($column['name'], 'yur_')))
                                            <td class="mini" style="text-align: right;">{{ $value[$column['name']] }}</td>
                                        @elseif ((is_numeric($value[$column['name']]) || str_ends_with($column['name'], 'rate') || str_ends_with($column['name'], 'duration')) && $column['name'] != 'name')
                                            <td style="text-align:right;">{{ $value[$column['name']] }}</td>
                                        @elseif ($column['name'] == 'date')
                                            <td style="width:80px;">{{ $value[$column['name']] }}</td>
                                        @elseif ($column['name'] == 'name' && isset($value['on_fire']))
                                            <td style="width:256px;">{{ $value[$column['name']] }}
                                                <span class="tooltip">
                                                <span class="circle"><img style="margin-top:-8px" src=" {{ Vite::asset("resources/images/badges/on-fire.png") }}" height="20px"></span>
                                                <span class="tooltiptext">This player has reached his peak rating within the last 30 days.</span>
                                            </span></td>
                                        @elseif ($column['name'] == 'name')
                                            <td style="width:256px;">{{ $value[$column['name']] }}</td>
                                        @else
                                            <td>{{ $value[$column['name']] }}
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
