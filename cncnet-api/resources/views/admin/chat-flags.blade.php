@extends('layouts.app')
@section('title', 'Flagged chat')

@php
    $reasons = \App\Models\GameChatFlag::REASONS;
    $time = fn (int $s) => sprintf('%d:%02d', intdiv($s, 60), $s % 60);
    $statusLabels = ['open' => 'Open', 'reviewed' => 'Reviewed', 'dismissed' => 'Dismissed'];
@endphp

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Flagged Chat</x-slot>

        <div class="mini-breadcrumb d-none d-lg-flex">
            <div class="mini-breadcrumb-item">
                <a href="/"><span class="material-symbols-outlined">home</span></a>
            </div>
            <div class="mini-breadcrumb-item">
                <a href="/admin"><span class="material-symbols-outlined">admin_panel_settings</span></a>
            </div>
        </div>
    </x-hero-with-video>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/"><span class="material-symbols-outlined">home</span></a></li>
                <li class="breadcrumb-item">
                    <a href="/admin"><span class="material-symbols-outlined pe-3">admin_panel_settings</span>Admin</a>
                </li>
                <li class="breadcrumb-item active">Flagged chat</li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4 mb-5">
        <div class="container">
            <form method="GET" action="{{ route('admin.chat-flags') }}" class="row g-2 align-items-center mb-4">
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        @foreach ($statusLabels as $value => $label)
                            <a href="{{ route('admin.chat-flags', array_filter(['status' => $value, 'ladder' => $ladderId])) }}"
                               class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="col-auto">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <select name="ladder" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All ladders</option>
                        @foreach ($ladders as $ladder)
                            <option value="{{ $ladder->id }}" @selected($ladderId === $ladder->id)>{{ $ladder->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if (Auth::user()->isAdmin())
                    <div class="col-auto ms-auto">
                        <a href="{{ route('admin.chat-filter') }}" class="btn btn-sm btn-outline-secondary">Word list and spam limits</a>
                    </div>
                @endif
            </form>

            @if ($games->isEmpty())
                <p class="text-muted">No {{ strtolower($statusLabels[$status]) }} flags.</p>
            @endif

            @foreach ($games as $row)
                @php
                    $game = $gameRows[$row->game_id] ?? null;
                    $history = $game?->ladderHistory;
                    $gameFlags = $flags[$row->game_id] ?? collect();
                @endphp
                <div class="player-box player-card mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        @if ($history)
                            <a href="/ladder/{{ $history->short }}/{{ $history->ladder->abbreviation }}/games/{{ $row->game_id }}">
                                Game {{ $row->game_id }}
                            </a>
                            <span class="text-muted">{{ $history->ladder->name }} &middot; {{ $game->created_at?->format('j M Y H:i') }}</span>
                        @else
                            <span>Game {{ $row->game_id }}</span>
                        @endif

                        <form method="POST" action="{{ route('admin.chat-flags.review', $row->game_id) }}" class="ms-auto d-flex gap-2">
                            @csrf
                            @if ($status === 'open')
                                <button name="status" value="reviewed" class="btn btn-sm btn-primary">Reviewed</button>
                                <button name="status" value="dismissed" class="btn btn-sm btn-outline-secondary">Dismiss</button>
                            @else
                                <span class="text-muted small align-self-center">
                                    {{ $statusLabels[$status] }}{{ $gameFlags->first()?->reviewer ? ' by ' . $gameFlags->first()->reviewer->name : '' }}
                                </span>
                                <button name="status" value="open" class="btn btn-sm btn-outline-secondary">Reopen</button>
                            @endif
                        </form>
                    </div>

                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach ($gameFlags as $flag)
                                <tr>
                                    <td style="width: 6rem">{{ $reasons[$flag->reason] ?? $flag->reason }}</td>
                                    <td style="width: 7rem" class="text-muted" title="Game time and frame">{{ $time($flag->seconds) }} <small>{{ $flag->frame }}</small></td>
                                    <td style="width: 10rem">{{ $flag->name }}</td>
                                    <td style="width: 12rem" class="text-muted">{{ $flag->detail }}</td>
                                    <td style="white-space: pre-line; word-break: break-word">{{ $flag->text }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            {{ $games->links() }}
        </div>
    </section>
@endsection
