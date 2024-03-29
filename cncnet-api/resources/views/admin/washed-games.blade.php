@extends('layouts.app')
@section('title', 'Washed Games')

@section('feature-image', '/images/feature/feature-index.jpg')
@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong>{{ $ladderHistory->ladder->name }}</strong><br/> Washed Games
                    </h1>
                </div>
            </div>
            <div class="mini-breadcrumb d-none d-lg-flex">
                <div class="mini-breadcrumb-item">
                    <a href="/" class="">
                    <span class="material-symbols-outlined">
                        home
                    </span>
                    </a>
                </div>
                <div class="mini-breadcrumb-item">
                    <a href="/admin" class="">
                    <span class="material-symbols-outlined">
                        admin_panel_settings
                    </span>
                    </a>
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
                    <a href="/admin">
                    <span class="material-symbols-outlined pe-3">
                        admin_panel_settings
                    </span>
                        Admin
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="mt-4">
        <div class="container">
            @include('components.pagination.paginate', ['paginator' => $washed_games->appends(request()->query())])

            <table class="table col-md-12">
                <thead>
                <tr>
                    <th>Ladder History</th>
                    <th>Game Id</th>
                    <th>Player 1</th>
                    <th>Player 2</th>
                    <th>Washed Date</th>
                    <th>Washed By</th>
                </tr>
                </thead>
                <tbody class="table">
                @foreach ($washed_games as $washed_game)

                        <?php
                        $gameUrl = \App\Models\URLHelper::getGameUrl($ladderHistory, $washed_game->game_id);

                        $playerGameReports = \App\Models\PlayerGameReport::where('game_id', $washed_game->game_id)->get();
                        $p1 = $playerGameReports[0]->player->username;
                        $p2 = $playerGameReports[1]->player->username;

                        $p1Url = \App\Models\URLHelper::getPlayerProfileUrl($ladderHistory, $p1);
                        $p2Url = \App\Models\URLHelper::getPlayerProfileUrl($ladderHistory, $p2);
                        ?>

                    <tr>
                        <td>{{ $ladderHistory->short }}</td>
                        <td><a href="{{ $gameUrl }}">{{ $washed_game->game_id }}</a></td>
                        <td><a href="{{ $p1Url }}">{{ $p1 }}</a></td>
                        <td><a href="{{ $p2Url }}">{{ $p2 }}</a></td>
                        <td>{{ $washed_game->created_at->format('F j, Y, g:i a T') }}</td>
                        <td>{{ $washed_game->username }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @include('components.pagination.paginate', ['paginator' => $washed_games->appends(request()->query())])
        </div>
    </section>
@endsection