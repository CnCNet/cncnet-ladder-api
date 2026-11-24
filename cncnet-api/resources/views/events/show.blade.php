@extends('layouts.app')
@section('title', $payload['event']['name'])

@php
    $ordinal = function (int $n) {
        $m = $n % 100;
        if ($m >= 11 && $m <= 13) { return $n . 'th'; }
        return $n . (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
    };
    $placeLabel = fn (array $e) => ($e['tied'] ? 'T-' : '') . $ordinal($e['place']);
    $money = fn ($v) => $v === null ? '' : '$' . number_format($v);
    $fmt = function ($v, $unit) {
        if ($unit === 'hours') { return number_format((float) $v, 2) . ' h'; }
        return $v . ($unit ? ' ' . $unit : '');
    };
    $statusLabels = [
        'upcoming' => 'Starts ' . $starts->format('j M Y'),
        'live' => 'Live',
        'pending_final' => 'Awaiting final results',
        'final' => 'Final',
    ];
    $statusClass = ['upcoming' => 'bg-secondary', 'live' => 'bg-success', 'pending_final' => 'bg-warning text-dark', 'final' => 'bg-primary'];
    $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
    $months = $payload['months'] ?? [];
@endphp

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">{{ $payload['event']['name'] }}</x-slot>
        <x-slot name="description">
            {{ $starts->format('j M Y') }} – {{ $ends->format('j M Y') }} · {{ strtoupper($payload['event']['ladder']) }}
        </x-slot>
    </x-hero-with-video>
@endsection

@section('content')
    <section class="pt-4 mt-4 mb-5 ladder-event">
        <div class="container">

            {{-- Header --}}
            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                <span class="badge fs-6 {{ $statusClass[$payload['status']] ?? 'bg-secondary' }}" data-event-status="{{ $payload['status'] }}">
                    {{ $statusLabels[$payload['status']] ?? $payload['status'] }}
                </span>
                @if ($computedAt)
                    <span class="text-muted small">Updated {{ $computedAt->diffForHumans() }}</span>
                @endif
            </div>

            @if ($payload['status'] !== 'upcoming' && $computedAt === null)
                <div class="alert alert-info">Results will appear shortly.</div>
            @endif

            {{-- Overall standings --}}
            @if ($payload['status'] !== 'upcoming')
                <h2 class="mt-4 mb-3">🏆 Overall Ladder Standings</h2>
                <div class="table-responsive mb-5">
                    <table class="table table-dark table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Place</th>
                                <th>Player</th>
                                @foreach ($months as $m)
                                    <th class="text-end">{{ $m['label'] }}</th>
                                @endforeach
                                <th class="text-end">Total</th>
                                <th class="text-end">Prize</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payload['standings'] as $row)
                                <tr class="{{ $row['place'] <= 3 ? 'fw-bold' : '' }}">
                                    <td>{{ $medals[$row['place']] ?? '' }} {{ $placeLabel($row) }}</td>
                                    <td>
                                        @if ($row['url'])
                                            <a href="{{ $row['url'] }}">{{ $row['name'] }}</a>
                                        @else
                                            {{ $row['name'] }}
                                        @endif
                                    </td>
                                    @foreach ($months as $m)
                                        <td class="text-end">{{ number_format($row[$m['key']] ?? 0) }}</td>
                                    @endforeach
                                    <td class="text-end">{{ number_format($row['points']) }}</td>
                                    <td class="text-end">{{ $money($row['prize']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ 4 + count($months) }}" class="text-muted">No standings yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Award sections --}}
            @foreach ($sections as $section)
                <h2 class="mt-5 mb-3" id="section-{{ $section['key'] }}">{{ $section['emoji'] }} {{ $section['title'] }}</h2>
                <div class="row g-3">
                    @foreach ($section['cards'] as $card)
                        @php
                            $result = $card['result'];
                            $leaders = $card['leaders'];
                            $runnersUp = array_slice(array_values(array_filter($card['entries'], fn ($e) => $e['place'] !== 1)), 0, 4);
                        @endphp
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card h-100 bg-dark text-light border-secondary" data-award="{{ $card['key'] }}">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h5 class="card-title mb-0">{{ $card['title'] }}</h5>
                                        <span class="badge bg-success ms-2">{{ implode(' / ', array_map($money, $card['prizes'])) }}</span>
                                    </div>
                                    <p class="card-text small text-muted mb-2">{{ $card['description'] }}</p>

                                    <div class="mb-2">
                                        @if ($card['estimated'])
                                            <span class="badge bg-info text-dark" data-bs-toggle="tooltip"
                                                  title="Estimated: only QM games are counted, live status is checked at match start, and a Twitch profile must be set on your ladder account.">estimated*</span>
                                        @endif
                                        @if ($card['type'] === 'manual' && !empty($card['selector']))
                                            <span class="badge bg-secondary">selected by {{ $card['selector'] }}</span>
                                        @elseif ($card['type'] === 'manual' && !empty($card['decided_by']))
                                            <span class="badge bg-secondary">{{ $card['decided_by'] }}</span>
                                        @endif
                                        @if ($result && $result['kind'] === 'override')
                                            <span class="badge bg-warning text-dark">set by organisers</span>
                                        @endif
                                        @if ($card['status'] === 'final')
                                            <span class="badge bg-primary">Final</span>
                                        @endif
                                    </div>

                                    <div class="mt-auto">
                                        @if ($card['status'] === 'upcoming')
                                            <p class="fs-5 text-muted mb-0">Starts {{ \Carbon\Carbon::parse($card['starts'])->format('j M') }}</p>
                                        @elseif ($card['type'] === 'manual')
                                            @if ($result)
                                                @include('events._result', ['result' => $result])
                                            @elseif (!empty($card['selector']))
                                                <p class="fs-5 mb-0">To be decided</p>
                                                <p class="small text-muted mb-0">{{ $card['candidate_count'] ?? 0 }} candidate wins — selected by {{ $card['selector'] }}</p>
                                            @else
                                                <p class="fs-5 mb-0">To be decided ({{ $card['decided_by'] ?? 'organisers' }})</p>
                                            @endif
                                        @else
                                            @if ($result)
                                                @include('events._result', ['result' => $result])
                                                <p class="small text-muted mt-2 mb-1">Computed ranking</p>
                                            @endif

                                            @if (count($leaders) === 0)
                                                <p class="fs-5 text-muted mb-0">No leader yet</p>
                                            @else
                                                @foreach ($leaders as $e)
                                                    <p class="{{ $result ? 'mb-0' : 'fs-4 fw-bold mb-0' }}">
                                                        {{ $placeLabel($e) }}
                                                        @if ($e['url'])<a href="{{ $e['url'] }}">{{ $e['name'] }}</a>@else{{ $e['name'] }}@endif
                                                        <span class="{{ $result ? '' : 'fs-6 fw-normal' }} text-muted">· {{ $fmt($e['value'], $card['unit']) }}</span>
                                                    </p>
                                                @endforeach
                                            @endif

                                            @if (count($runnersUp))
                                                <ul class="list-unstyled small text-muted mt-2 mb-0">
                                                    @foreach ($runnersUp as $e)
                                                        <li>
                                                            {{ $placeLabel($e) }}
                                                            @if ($e['url'])<a href="{{ $e['url'] }}">{{ $e['name'] }}</a>@else{{ $e['name'] }}@endif
                                                            · {{ $fmt($e['value'], $card['unit']) }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        @endif

                                        @foreach ($card['notes'] as $note)
                                            <p class="small fst-italic text-warning mt-2 mb-0">{{ $note }}</p>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <p class="small text-muted mt-5" id="streaming-footnote">
                * Streaming hours are estimated: only QM games are counted, a player's live status is checked when the match starts,
                and it requires a Twitch profile set on the ladder account before queueing.
                Holiday days run 00:00–23:59 UTC. Players are shown by account, so alts and renamed players are combined.
            </p>
        </div>
    </section>
@endsection
