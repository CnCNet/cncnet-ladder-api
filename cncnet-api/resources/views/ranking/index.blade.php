@extends('layouts.app')
@section('title', 'Ladder Elo Ratings')
@section('feature-video', \App\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6">
                    <img src="/images/games/{{ $gameMode }}/logo.png" alt="logo" class="d-block img-fluid me-lg-0 ms-lg-auto" />
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">Ladder</strong> <span>ELO Ratings</span>
                    </h1>

                    <p class="lead text-uppercase">
                        <small>Last updated - <strong>(2023-02-05 12:00 AM GMT)</strong></small>
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

        /* Bold text for combined elo. */
        #ratings tr th {
            text-align: left;
            font-size: 18px;
            color: #ffffff;
            font-family: Helvetica, Arial, sans-serif;
        }

        /* Align rank symbol right. */
        #ratings tr th:nth-child(1) {
            text-align: right;
        }

        /* Smaller font for faction specific elo. */
        #ratings tr th:nth-child(n+7) {
            text-align: left;
            font-size: 14px;
            color: #ffffff;
            font-family: Helvetica, Arial, sans-serif;
            font-weight: normal;
        }

        /* General text. */
        #ratings tr td {
            text-align: right;
            font-size: 12px;
            color: #eeeeee;
            font-family: Verdana, Arial, sans-serif;
        }

        /* Name and status are aligned left. */
        #ratings tr td:nth-child(2),
        td:nth-child(3) {
            text-align: left;
        }

        /* Make ranks, names and elo bold and bigger sized. */
        #ratings tr td:nth-child(1),
        #ratings td:nth-child(2),
        #ratings td:nth-child(4) {
            font-weight: normal;
            font-size: 20px;
        }

        /* Make combined deviation and games bold. */
        #ratings tr td:nth-child(5),
        #ratings td:nth-child(6) {
            font-weight: bold;
            font-size: 14px;
        }

        /* Make factions specific values a little darker. */
        #ratings tr td:nth-child(n+7) {
            color: #dddddd
        }

        /* Status new in red. */
        #ratings tr:nth-child(n+2) td:nth-child(3) {
            color: #ff8040;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        /* Alternating background color for rows. */
        #ratings tr:nth-child(even) {
            background-color: #505050;
        }

        #ratings tr:nth-child(odd) {
            background-color: #202530;
        }

        /* Background colors for the two header rows. */
        #ratings tr:nth-child(1) {
            background-color: #000000;
        }

        #ratings tr:nth-child(2) {
            background-color: #608040;
        }

        #ratings thead tr td:first-child,
        #ratings tbody tr th:first-child {
            width: 32px;
            min-width: 32px;
            max-width: 32px;
        }

        #ratings thead tr td:nth-child(4),
        #ratings tbody tr th:nth-child(4) {
            width: 64px;
            min-width: 64px;
            max-width: 64px;
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
            /*font-weight:bold;*/
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
    </style>
@endsection

@section('content')
    <div class="ladder-index">
        <section class="pt-5 pb-5">
            <div class="container">

                <h5>Game mode</h5>
                <div class="mb-5">
                    @foreach ($gameModes as $k => $gm)
                        <a href="?list={{ $index }}&mode={{ $gameModesShort[$k] }}"
                            class="btn {{ $gameModesShort[$k] == $gameMode ? 'btn-primary' : 'btn-secondary' }}">{{ $gm }}</a>
                    @endforeach
                </div>

                <h5>Players</h5>
                <div class="mb-5">
                    @foreach ($links as $k => $link)
                        <a href="?list={{ $k }}&mode={{ $gameMode }}"
                            class="btn {{ $k == $index ? 'btn-primary' : 'btn-secondary' }}">{{ $link }}</a>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table id="ratings" style="border-collapse: collapse; width: 100%; margin-left: auto; margin-right: auto;" class="table">
                        <tbody>
                            <tr>
                                <td colspan="6" style="border: 0px;"></td>
                                <td colspan="3" style="text-align:center;font-weight: normal;font-size: 14px;">
                                    <img src="/images/game-icons/iraq.png" height="24px" style="vertical-align: middle;">
                                    <span style="vertical-align: middle;">
                                        Soviet
                                    </span>
                                </td>
                                <td colspan="3" style="text-align:center;font-weight: normal;font-size: 14px;">
                                    <img src="/images/game-icons/america.png" height="24px" style="vertical-align: middle;">
                                    <span style="vertical-align: middle;">
                                        Allied
                                    </span>
                                </td>
                                <td class="optional" colspan="3" style="text-align:center;font-weight: normal;font-size: 14px;">
                                    <img src="/images/game-icons/yuri.png" height="24px" style="vertical-align: middle;">
                                    <span style="vertical-align: middle;">
                                        Yuri
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th style="width:100px">#</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Elo</th>
                                <th>Deviation</th>
                                <th>Games</th>
                                <th>Elo</th>
                                <th>Deviation</th>
                                <th>Games</th>
                                <th>Elo</th>
                                <th>Deviation</th>
                                <th>Games</th>
                                <th class="optional">Elo</th>
                                <th class="optional">Deviation</th>
                                <th class="optional">Games</th>
                            </tr>

                            @foreach ($data as $value)
                                <tr>
                                    <td>{{ $value['rank'] }}</td>
                                    <td>{{ $value['name'] }}</td>
                                    <td>{{ $value['status'] }}</td>

                                    @if ($value['elo'] < 0)
                                        <td>{{ $value['mix_elo'] }}</td>
                                        <td>{{ $value['mix_deviation'] }}</td>
                                        <td>{{ $value['mix_games'] }}</td>
                                    @else
                                        <td>{{ $value['elo'] }}</td>
                                        <td>{{ $value['deviation'] }} </td>
                                        <td>{{ $value['game_count'] }}</td>
                                    @endif

                                    @if (array_key_exists('sov_games', $value))
                                        <td>{{ $value['sov_elo'] }}</td>
                                        <td>{{ $value['sov_deviation'] }}</td>
                                        <td>{{ $value['sov_games'] }}</td>
                                    @else
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    @endif

                                    @if (array_key_exists('all_games', $value))
                                        <td>{{ $value['all_elo'] }}</td>
                                        <td>{{ $value['all_deviation'] }}</td>
                                        <td>{{ $value['all_games'] }}</td>
                                    @else
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    @endif

                                    @if (array_key_exists('yur_games', $value))
                                        <td>{{ $value['yur_elo'] }}</td>
                                        <td>{{ $value['yur_deviation'] }}</td>
                                        <td>{{ $value['yur_games'] }}</td>
                                    @else
                                        <td class="optional"></td>
                                        <td class="optional"></td>
                                        <td class="optional"></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>


    @if ($gameMode != 'yr')
        <style>
            .optional {
                display: none;
            }
        </style>
    @endif
@endsection
