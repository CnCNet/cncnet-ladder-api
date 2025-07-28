@extends('layouts.app')
@section('title', 'Observed Games')

@section('feature')
<x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
    <x-slot name="title">{{ $ladder->abbreviation }} Observed Games</x-slot>
    <x-slot name="description">
        View {{ $ladder->abbreviation }} Observed Games
    </x-slot>

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
</x-hero-with-video>
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
    <div class="mb-4">
        <form id="historyForm" class="form-inline">
            <label for="ladderHistoryShort" class="me-2">Select Ladder History:</label>
            <select name="ladderHistoryShort" id="ladderHistoryShort" class="form-select d-inline w-auto me-2">
                @foreach ($histories as $history)
                <option value="{{ $history->short }}" {{ isset($ladderHistoryShort) && $ladderHistoryShort == $history->short ? 'selected' : '' }}>
                    {{ $history->short }}
                </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-primary" onclick="goToLadderHistory()">View</button>
        </form>
    </div>

    <script>
        function goToLadderHistory() {
            const short = document.getElementById('ladderHistoryShort').value;
            const baseUrl = "{{ url('/admin/observedGames/' . $ladder->abbreviation) }}";
            window.location.href = `${baseUrl}?ladderHistoryShort=${short}`;
        }
    </script>

    <div class="container">
        @include('components.pagination.paginate', ['paginator' => $observedGames->appends(request()->query())])

        <table class="table col-md-12">
            <thead>
                <tr>
                    <th>Ladder History</th>
                    <th>Game Id</th>
                    <th>Players</th>
                    <th>Observers</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody class="table">
                @foreach ($observedGames as $observedGame)
                @php
                $gameUrl = \App\Models\URLHelper::getGameUrl($ladderHistory, $observedGame->id);
                @endphp

                <tr>
                    <td>{{ $ladderHistory->short }}</td>
                    <td><a href="{{ $gameUrl }}">{{ $observedGame->id }}</a></td>

                    {{-- Players --}}
                    <td>
                        <ul class="list-unstyled mb-0">
                            @foreach ($observedGame->players as $report)
                            @php
                            $player = $report->player;
                            $playerUrl = \App\Models\URLHelper::getPlayerProfileUrl($ladderHistory, $player);
                            @endphp
                            <li><a href="{{ $playerUrl }}">{{ $player->username }}</a></li>
                            @endforeach
                        </ul>
                    </td>

                    {{-- Observers --}}
                    <td>
                        <ul class="list-unstyled mb-0">
                            @foreach ($observedGame->observers as $report)
                            @php
                            $player = $report->player;
                            $observerUrl = \App\Models\URLHelper::getPlayerProfileUrl($ladderHistory, $player);
                            $twitchUrl = $player->user->getTwitchProfile();
                            @endphp
                            <li>
                                Player: <a href="{{ $observerUrl }}">{{ $player->username }}</a>
                                @if ($twitchUrl)
                                | Twitch: <a href="{{ $twitchUrl }}" target="_blank" rel="noopener noreferrer">{{ $twitchUrl }}</a>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    </td>

                    <td>{{ $observedGame->created_at->format('F j, Y, g:i a T') }}</td>
                </tr>
                @endforeach
            </tbody>

        </table>

        @include('components.pagination.paginate', ['paginator' => $observedGames->appends(request()->query())])
    </div>
</section>
@endsection