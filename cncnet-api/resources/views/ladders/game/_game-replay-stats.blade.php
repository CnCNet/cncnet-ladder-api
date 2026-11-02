{{--
    Statistics read out of the game's replay: see ReplayStatsBuilder for what each figure means.
    Only rendered when the ladder lets this viewer download its replays.
--}}
@if (!empty($replayStats))
    @php
        $rs = $replayStats;
        $riPlayers = collect($rs['players'])->keyBy('house');
        $riTime = fn ($s) => sprintf('%d:%02d', intdiv((int) $s, 60), ((int) $s) % 60);
        $riName = fn ($house) => $riPlayers[$house]['name'] ?? "Player {$house}";
        $riColour = fn ($house) => $riPlayers[$house]['color'] ?? 'none';
        $riTeamOf = collect($rs['players'])->mapWithKeys(fn ($p) => [$p['house'] => $p['team']]);
        $riHighlightValue = fn ($h) => match ($h['key']) {
            'comeback' => 'from ' . $h['value'] . '%',
            default => number_format($h['value']),
        };
        $riResultBadge = function ($p) use ($riTime) {
            $at = $p['defeatedAt'] !== null ? ' ' . $riTime($p['defeatedAt']) : '';
            return match ($p['result']) {
                'won' => ['ri-badge-won', 'Won'],
                'resigned' => ['ri-badge-lost', 'Resigned' . $at],
                'disconnected' => ['ri-badge-dropped', 'Disconnected' . $at],
                'defeated' => ['ri-badge-lost', 'Defeated' . $at],
                default => null,
            };
        };

        // Teams of more than one player, for the team table and the contribution bars.
        $riSharedTeams = collect($rs['teams'])->filter(fn ($t) => count($t['members']) > 1);
        $riTeamGame = $riSharedTeams->isNotEmpty();
        $riTeamName = fn ($team) => collect($team['members'])->map($riName)->join(' & ');
        $riTeamBadge = fn ($team) => match ($team['result']) {
            'won' => ['ri-badge-won', 'Won'],
            'defeated' => ['ri-badge-lost', 'Defeated'],
            default => null,
        };
        $riMeasures = [
            'income' => 'Income',
            'spent' => 'Spent',
            'peakArmy' => 'Peak army value',
            'unitsBuilt' => 'Units built',
            'unitsKilled' => 'Units killed',
            'buildingsKilled' => 'Buildings killed',
            'unitsLost' => 'Units lost',
        ];

        // Tech timings: the key buildings anyone placed, and who placed each one first.
        $riTechKeys = collect(\App\Http\Services\Replay\ReplayStatsBuilder::KEY_BUILDING_NAMES)
            ->filter(fn ($label, $key) => collect($rs['players'])->contains(fn ($p) => isset($p['tech'][$key])));
        $riFastest = $riTechKeys->map(fn ($label, $key) => collect($rs['players'])->map(fn ($p) => $p['tech'][$key]['t'] ?? null)->filter(fn ($t) => $t !== null)->min());
    @endphp

    <section class="replay-insights">
        <div class="container-xl">
            <div class="ri-header">
                <h3>Replay statistics</h3>
            </div>

            @if (!empty($rs['sync']))
                @php $riSyncPeers = collect($rs['sync']['peers']); @endphp
                <div class="ri-sync-warning">
                    This game went out of sync at {{ $riTime($rs['sync']['t']) }}
                    @if ($riSyncPeers->count() > 0 && $riSyncPeers->count() >= count($rs['players']) - 1)
                        <span class="ri-muted">&ndash; from there on, {{ isset($rs['recorder']) ? $riName($rs['recorder']) . "'s" : "the recording player's" }} game matched nobody else's</span>
                    @elseif ($riSyncPeers->count() > 0)
                        <span class="ri-muted">&ndash; {{ $riSyncPeers->map($riName)->join(', ', ' and ') }} no longer matched the recording player's game</span>
                    @endif
                </div>
            @endif

            @if (count($rs['highlights']) > 0)
                <div class="ri-highlights">
                    @foreach ($rs['highlights'] as $h)
                        <div class="ri-highlight ri-colour-{{ $riColour($h['house']) }}">
                            <div class="ri-highlight-title">{{ $h['title'] }}</div>
                            <div class="ri-highlight-value">{{ $riHighlightValue($h) }}</div>
                            <div class="ri-highlight-player">
                                {{ $h['key'] === 'comeback' ? collect($rs['teams'][$riTeamOf[$h['house']]]['members'])->map($riName)->join(' & ') : $riName($h['house']) }}
                                @if (isset($h['t']))
                                    <span class="ri-muted">at {{ $riTime($h['t']) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (!empty($rs['momentum']))
                <div class="ri-card">
                    <div class="ri-card-title">
                        Momentum
                    </div>
                    <div class="ri-momentum-legend">
                        @foreach ($rs['teams'] as $k => $team)
                            <span class="ri-legend-item">
                                <span class="ri-dot ri-colour-{{ $riColour($team['members'][0]) }}"></span>
                                {{ collect($team['members'])->map($riName)->join(' & ') }}
                                @if ($team['won'])
                                    <span class="ri-trophy">(won)</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                    <div class="ri-chart ri-chart-momentum"><canvas id="riMomentum"></canvas></div>
                </div>
            @endif

            @if ($riTeamGame)
                <div class="ri-card">
                    <div class="ri-card-title">Teams</div>
                    <div class="table-responsive">
                        <table class="ri-table">
                            <thead>
                                <tr>
                                    <th>Team</th>
                                    <th>Result</th>
                                    <th>Income</th>
                                    <th>Spent</th>
                                    <th>Units</th>
                                    <th>Buildings</th>
                                    <th>Kills</th>
                                    <th>Losses</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rs['teams'] as $team)
                                    @php $t = $team['totals']; $badge = $riTeamBadge($team); @endphp
                                    <tr>
                                        <td>
                                            @foreach ($team['members'] as $house)
                                                <span class="ri-legend-item"><span class="ri-dot ri-colour-{{ $riColour($house) }}"></span>{{ $riName($house) }}</span>
                                            @endforeach
                                        </td>
                                        <td>@if ($badge)<span class="ri-badge {{ $badge[0] }}" style="margin-left: 0">{{ $badge[1] }}</span>@endif</td>
                                        <td>{{ number_format($t['income']) }}</td>
                                        <td>{{ number_format($t['spent']) }}</td>
                                        <td>{{ number_format($t['unitsBuilt']) }}</td>
                                        <td>{{ number_format($t['buildingsBuilt']) }}</td>
                                        <td>{{ number_format($t['unitsKilled'] + $t['buildingsKilled']) }}</td>
                                        <td>{{ number_format($t['unitsLost'] + $t['buildingsLost']) }}</td>
                                        <td>{{ number_format($t['score']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($riTechKeys->isNotEmpty())
                        <div class="ri-section-title">Team tech timings <span class="ri-hint">the first of each team to place it</span></div>
                        <div class="table-responsive">
                            <table class="ri-table">
                                <thead>
                                    <tr>
                                        <th>Team</th>
                                        @foreach ($riTechKeys as $key => $label)
                                            <th>{{ $label }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rs['teams'] as $team)
                                        <tr>
                                            <td>{{ $riTeamName($team) }}</td>
                                            @foreach ($riTechKeys as $key => $label)
                                                @php $tech = $team['tech'][$key] ?? null; @endphp
                                                <td @if ($tech) title="{{ $riName($tech['house']) }}: {{ $tech['name'] }}" @endif class="{{ $tech && $tech['t'] === $riFastest[$key] ? 'ri-fastest' : '' }}">
                                                    {{ $tech ? $riTime($tech['t']) : '–' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

            <div class="ri-card">
                <div class="ri-card-title">
                    Timeline
                </div>
                @if ($riTeamGame)
                    <div class="ri-tabs ri-tabs-mode" role="tablist">
                        <button type="button" class="ri-tab active" data-mode="players">Players</button>
                        <button type="button" class="ri-tab" data-mode="teams">Teams</button>
                    </div>
                @endif
                <div class="ri-tabs" role="tablist">
                    <button type="button" class="ri-tab active" data-metric="army">Army value</button>
                    <button type="button" class="ri-tab" data-metric="base">Base value</button>
                    <button type="button" class="ri-tab" data-metric="incomeRate">Income / min</button>
                    <button type="button" class="ri-tab" data-metric="spent">Spent</button>
                    <button type="button" class="ri-tab" data-metric="cash">Money</button>
                    <button type="button" class="ri-tab" data-metric="armySize">Army size</button>
                    <button type="button" class="ri-tab" data-metric="buildings">Buildings</button>
                    <button type="button" class="ri-tab" data-metric="power">Power</button>
                    <button type="button" class="ri-tab" data-metric="kills">Kills</button>
                    <button type="button" class="ri-tab" data-metric="losses">Losses</button>
                    <button type="button" class="ri-tab" data-metric="trade">Kills − losses</button>
                    <button type="button" class="ri-tab" data-metric="score">Score</button>
                </div>
                <div class="ri-chart ri-chart-timeline"><canvas id="riTimeline"></canvas></div>
            </div>

            @if ($riTechKeys->isNotEmpty())
                <div class="ri-card">
                    <div class="ri-card-title">Tech timings</div>
                    <div class="table-responsive">
                        <table class="ri-table">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    @foreach ($riTechKeys as $key => $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rs['players'] as $p)
                                    <tr class="ri-colour-{{ $p['color'] ?? 'none' }}">
                                        <td><span class="ri-player-cell">{{ $p['name'] }}</span></td>
                                        @foreach ($riTechKeys as $key => $label)
                                            @php $tech = $p['tech'][$key] ?? null; @endphp
                                            <td @if ($tech) title="{{ $tech['name'] }}" @endif class="{{ $tech && $tech['t'] === $riFastest[$key] ? 'ri-fastest' : '' }}">
                                                {{ $tech ? $riTime($tech['t']) : '–' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="ri-grid-2">
                <div class="ri-card">
                    <div class="ri-card-title">Income</div>
                    <div class="ri-chart ri-chart-income"><canvas id="riIncome"></canvas></div>
                </div>
                <div class="ri-card">
                    <div class="ri-card-title">Value built</div>
                    <div class="ri-chart ri-chart-spending"><canvas id="riSpending"></canvas></div>
                </div>
            </div>

            @if ($riTeamGame)
                {{-- Each bar is the whole game's total, split between the teams. --}}
                <div class="ri-card">
                    <div class="ri-card-title">Team share</div>
                    @foreach (['income' => 'Income', 'valueBuilt' => 'Value built'] as $measure => $label)
                        @php
                            $parts = collect($rs['teams'])->map(fn ($team) => [
                                'team'  => $team,
                                'value' => max(0, $measure === 'valueBuilt' ? array_sum($team['valueBuilt'] ?? []) : ($team['totals'][$measure] ?? 0)),
                            ]);
                            $sum = $parts->sum('value');
                        @endphp
                        <div class="ri-carry-row">
                            <span class="ri-carry-label">{{ $label }}</span>
                            <div class="ri-carry-bar">
                                @if ($sum > 0)
                                    @foreach ($parts as $part)
                                        @if ($part['value'] > 0)
                                            @php $share = $part['value'] / $sum; @endphp
                                            <div class="ri-carry-segment ri-colour-{{ $riColour($part['team']['members'][0]) }}"
                                                style="width: {{ round($share * 100, 2) }}%"
                                                title="{{ $riTeamName($part['team']) }}: {{ number_format($part['value']) }} ({{ round($share * 100) }}%)">
                                                <span>{{ $riTeamName($part['team']) }} {{ number_format($part['value']) }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                            <span class="ri-carry-total">{{ number_format($sum) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($riSharedTeams->isNotEmpty())
                <div class="ri-card">
                    <div class="ri-card-title">
                        Contribution
                    </div>
                    @foreach ($riSharedTeams as $team)
                        <div class="ri-carry-team">
                            <div class="ri-carry-team-name">
                                {{ collect($team['members'])->map($riName)->join(' & ') }}
                                @if ($team['won'])
                                    <span class="ri-trophy">(won)</span>
                                @endif
                            </div>
                            @foreach ($riMeasures as $measure => $label)
                                @php
                                    $parts = collect($team['members'])->map(fn ($h) => [
                                        'house' => $h,
                                        'value' => max(0, $riPlayers[$h]['totals'][$measure] ?? 0),
                                    ]);
                                    $sum = $parts->sum('value');
                                @endphp
                                <div class="ri-carry-row">
                                    <span class="ri-carry-label">{{ $label }}</span>
                                    <div class="ri-carry-bar">
                                        @if ($sum > 0)
                                            @foreach ($parts as $part)
                                                @if ($part['value'] > 0)
                                                    @php $share = $part['value'] / $sum; @endphp
                                                    <div class="ri-carry-segment ri-colour-{{ $riColour($part['house']) }}"
                                                        style="width: {{ round($share * 100, 2) }}%"
                                                        title="{{ $riName($part['house']) }}: {{ number_format($part['value']) }} ({{ round($share * 100) }}%)">
                                                        <span>{{ $riName($part['house']) }} {{ round($share * 100) }}%</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                    <span class="ri-carry-total">{{ number_format($sum) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Staff only: the response times each player's game reported about its own connection. --}}
            @if (!empty($replayStaff) && !empty($rs['connection']))
                <div class="ri-card">
                    <div class="ri-card-title">
                        Connection
                        <span class="ri-hint">response time reported by each player's game, lower is better</span>
                    </div>
                    <div class="ri-connection-summary">
                        @foreach ($rs['connection'] as $house => $c)
                            <span class="ri-legend-item">
                                <span class="ri-dot ri-colour-{{ $riColour($house) }}"></span>
                                {{ $riName($house) }}
                                <span class="ri-muted">{{ number_format($c['median']) }} ms typical, {{ !empty($c['capped']) ? 'over ' : '' }}{{ number_format($c['peak']) }} ms worst</span>
                            </span>
                        @endforeach
                    </div>
                    <div class="ri-chart ri-chart-connection"><canvas id="riConnection"></canvas></div>
                </div>
            @endif

            <div class="ri-players">
                @foreach ($rs['players'] as $p)
                    <div class="ri-player ri-colour-{{ $p['color'] ?? 'none' }}">
                        <div class="ri-player-header">
                            <span class="ri-player-name">{{ $p['name'] }}</span>
                            <span class="ri-muted">{{ $p['country'] }}</span>
                            @if (($badge = $riResultBadge($p)) !== null)
                                <span class="ri-badge {{ $badge[0] }}">{!! $badge[1] !!}</span>
                            @endif
                        </div>

                        <div class="ri-player-stats">
                            <div><span>{{ number_format($p['totals']['income']) }}</span>earned</div>
                            <div><span>{{ number_format($p['totals']['spent']) }}</span>spent</div>
                            <div><span>{{ number_format($p['totals']['peakArmy']) }}</span>peak army</div>
                            <div><span>{{ number_format($p['totals']['unitsBuilt'] ?? 0) }}</span>units built</div>
                            <div><span>{{ number_format($p['totals']['buildingsBuilt'] ?? 0) }}</span>buildings built</div>
                            <div><span>{{ $p['totals']['unitsKilled'] + $p['totals']['buildingsKilled'] }}</span>destroyed</div>
                            <div><span>{{ $p['totals']['unitsLost'] + $p['totals']['buildingsLost'] }}</span>lost</div>
                        </div>

                        @if (count($p['buildOrder']) > 0)
                            <div class="ri-section-title">Build order</div>
                            <div class="ri-cameo-strip ri-build-order">
                                @foreach ($p['buildOrder'] as $b)
                                    <div class="ri-cameo-item" title="{{ $b['name'] }} placed at {{ $riTime($b['t']) }}, frame {{ number_format($b['f'] ?? 0) }}">
                                        <div class="ri-cameo yr-cameo cameo-{{ $b['cameo'] }}"></div>
                                        <span class="ri-cameo-label">{{ $riTime($b['t']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @foreach (['built' => 'Built most', 'killed' => 'Destroyed most', 'lost' => 'Lost most'] as $list => $title)
                            @if (count($p[$list] ?? []) > 0)
                                <div class="ri-section-title">{{ $title }}</div>
                                <div class="ri-cameo-strip">
                                    @foreach ($p[$list] as $b)
                                        <div class="ri-cameo-item" title="{{ $b['name'] }}">
                                            <div class="ri-cameo yr-cameo cameo-{{ $b['cameo'] }}"></div>
                                            <span class="ri-cameo-label">{{ $b['count'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>

        </div>
    </section>

    <script src="/js/chart.min.js"></script>
    <script>
        (function () {
            const stats = @json($rs);
            const palette = {
                yellow: '#ffe14d', red: '#ff4545', orange: '#ffa31a', pink: '#ff5cc0',
                purple: '#b65cff', blue: '#4d86ff', teal: '#1fe0ef', green: '#3cff6e',
            };
            const neutral = '#9aa4b2';
            const players = stats.players;
            const colourOf = house => {
                const p = players.find(x => x.house === house);
                return (p && palette[p.color]) || neutral;
            };
            const nameOf = house => (players.find(x => x.house === house) || {}).name || ('Player ' + house);
            const time = s => Math.floor(s / 60) + ':' + String(Math.round(s % 60)).padStart(2, '0');
            const thousands = v => (Math.abs(v) >= 1000 ? (v / 1000) + 'k' : v);
            const series = (metric, house) => (stats.timeline.series[metric] || {})[house] || [];

            Chart.defaults.color = '#9aa4b2';
            Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
            Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

            // Superweapon and defeat markers: a dashed line with a dot at the top, named in the tooltip.
            const markers = {
                id: 'riMarkers',
                afterDatasetsDraw(chart) {
                    const x = chart.scales.x;
                    const area = chart.chartArea;
                    const ctx = chart.ctx;
                    ctx.save();
                    stats.events.forEach(e => {
                        const px = x.getPixelForValue(e.t);
                        if (px < area.left || px > area.right) return;
                        ctx.strokeStyle = colourOf(e.house);
                        ctx.globalAlpha = 0.55;
                        ctx.setLineDash([3, 4]);
                        ctx.beginPath();
                        ctx.moveTo(px, area.top + 12);
                        ctx.lineTo(px, area.bottom);
                        ctx.stroke();
                        ctx.globalAlpha = 1;
                        ctx.setLineDash([]);
                        ctx.fillStyle = colourOf(e.house);
                        ctx.beginPath();
                        ctx.arc(px, area.top + 6, e.type === 'superweapon' ? 3 : 4, 0, Math.PI * 2);
                        ctx.fill();
                    });
                    ctx.restore();
                },
            };

            const timeAxis = max => ({
                type: 'linear', min: 0, max: max,
                ticks: { callback: v => time(v), maxTicksLimit: 12 },
                grid: { display: false },
            });

            const markerTooltip = items => {
                const t = items[0].parsed.x;
                const near = stats.events.filter(e => Math.abs(e.t - t) <= 4);
                return near.map(e => e.type === 'superweapon' ? nameOf(e.house) + ' launched ' + e.label : e.label);
            };

            // Momentum: each team's share of the lead, stacked to 100%.
            if (stats.momentum) {
                const m = stats.momentum;
                new Chart(document.getElementById('riMomentum'), {
                    type: 'line',
                    data: {
                        datasets: stats.teams.map((team, k) => ({
                            label: team.members.map(nameOf).join(' & '),
                            data: m.t.map((t, i) => ({ x: t, y: Math.round(m.shares[k][i] * 1000) / 10 })),
                            borderColor: colourOf(team.members[0]),
                            backgroundColor: colourOf(team.members[0]) + '55',
                            fill: k === 0 ? 'origin' : '-1', pointRadius: 0, borderWidth: 1.5, tension: 0.25,
                        })),
                    },
                    options: {
                        maintainAspectRatio: false, animation: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: timeAxis(stats.duration),
                            y: { stacked: true, min: 0, max: 100, ticks: { callback: v => v + '%', stepSize: 50 } },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: items => time(items[0].parsed.x),
                                    label: item => ' ' + item.dataset.label + ': ' + item.parsed.y + '%',
                                    footer: markerTooltip,
                                },
                            },
                        },
                    },
                    plugins: [markers],
                });
            }

            // Timeline: one line per player, or per team summed, switchable metric.
            const teamName = team => team.members.map(nameOf).join(' & ');
            let timelineMode = 'players';
            let timelineMetric = 'army';
            const datasetsFor = metric => timelineMode === 'teams'
                ? stats.teams.map((team, k) => ({
                    label: teamName(team),
                    data: ((stats.timeline.teamSeries || {})[metric] || {})[k] || [],
                    borderColor: colourOf(team.members[0]),
                    backgroundColor: colourOf(team.members[0]),
                    pointRadius: 0, borderWidth: 2, tension: 0.15,
                })).map(d => ({ ...d, data: d.data.map((v, i) => ({ x: stats.timeline.t[i], y: v })) }))
                : players.map(p => ({
                    label: p.name,
                    data: series(metric, p.house).map((v, i) => ({ x: stats.timeline.t[i], y: v })),
                    borderColor: colourOf(p.house),
                    backgroundColor: colourOf(p.house),
                    pointRadius: 0, borderWidth: 2, tension: 0.15,
                }));
            const credits = ['army', 'base', 'incomeRate', 'spent', 'cash', 'score'];
            const signed = ['power', 'trade'];
            const timeline = new Chart(document.getElementById('riTimeline'), {
                type: 'line',
                data: { datasets: datasetsFor('army') },
                options: {
                    maintainAspectRatio: false, animation: { duration: 250 },
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: timeAxis(stats.duration),
                        y: { beginAtZero: true, ticks: { callback: thousands } },
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
                        tooltip: {
                            itemSort: (a, b) => b.parsed.y - a.parsed.y,
                            callbacks: {
                                title: items => time(items[0].parsed.x),
                                label: item => ' ' + item.dataset.label + ': ' + item.parsed.y.toLocaleString(),
                                footer: markerTooltip,
                            },
                        },
                    },
                },
                plugins: [markers],
            });

            const redrawTimeline = () => {
                timeline.data.datasets = datasetsFor(timelineMetric);
                timeline.options.scales.y.ticks.callback = credits.includes(timelineMetric) ? thousands : v => v;
                timeline.options.scales.y.beginAtZero = !signed.includes(timelineMetric);
                timeline.update();
            };
            document.querySelectorAll('.replay-insights .ri-tab[data-metric]').forEach(button => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('.replay-insights .ri-tab[data-metric]').forEach(b => b.classList.remove('active'));
                    button.classList.add('active');
                    timelineMetric = button.dataset.metric;
                    redrawTimeline();
                });
            });
            document.querySelectorAll('.replay-insights .ri-tab[data-mode]').forEach(button => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('.replay-insights .ri-tab[data-mode]').forEach(b => b.classList.remove('active'));
                    button.classList.add('active');
                    timelineMode = button.dataset.mode;
                    redrawTimeline();
                });
            });

            // Income sources: one stacked bar per player.
            const sourceColours = {
                Harvested: '#e8c547', Buildings: '#5fb0ff', Crates: '#b18cff', Sold: '#9aa4b2',
                Grinding: '#ff6b6b', Stolen: '#3fe0a0', Bounty: '#ff9f43', Superweapon: '#ff5cc0',
                Warhead: '#c0c0c0', Other: '#6c757d', Unclassified: '#495057',
            };
            const sourceNames = { Buildings: 'Oil derricks & buildings', Sold: 'Selling', Harvested: 'Harvesting' };
            const sources = [...new Set(players.flatMap(p => Object.keys(p.incomeSources)))];
            const stackedBars = (id, datasets, labels) => document.getElementById(id) && new Chart(document.getElementById(id), {
                type: 'bar',
                data: { labels: labels || players.map(p => p.name), datasets: datasets },
                options: {
                    indexAxis: 'y', maintainAspectRatio: false, animation: false,
                    scales: {
                        x: { stacked: true, ticks: { callback: thousands } },
                        y: { stacked: true, grid: { display: false } },
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12 } },
                        tooltip: { callbacks: { label: item => ' ' + item.dataset.label + ': ' + item.parsed.x.toLocaleString() } },
                    },
                },
            });
            stackedBars('riIncome', sources.map(source => ({
                label: sourceNames[source] || source,
                data: players.map(p => p.incomeSources[source] || 0),
                backgroundColor: sourceColours[source] || '#6c757d',
                borderRadius: 3,
            })));

            // Value built: credits' worth of each kind of thing that joined the player's side.
            const kinds = { infantry: ['Infantry', '#9fe3b2'], vehicle: ['Vehicles', '#ffd36b'], aircraft: ['Aircraft', '#b18cff'], building: ['Buildings', '#5fb0ff'] };
            stackedBars('riSpending', Object.entries(kinds).map(([kind, [label, colour]]) => ({
                label: label,
                data: players.map(p => (p.valueBuilt || {})[kind] || 0),
                backgroundColor: colour,
                borderRadius: 3,
            })));

            // Connection: each player's reported response time.
            if (stats.connection) {
                new Chart(document.getElementById('riConnection'), {
                    type: 'line',
                    data: {
                        datasets: Object.entries(stats.connection).map(([house, c]) => ({
                            label: nameOf(Number(house)),
                            data: c.points.map(([t, ms]) => ({ x: t, y: ms })),
                            borderColor: colourOf(Number(house)),
                            backgroundColor: colourOf(Number(house)),
                            pointRadius: 0, borderWidth: 1.5, tension: 0.2, stepped: false,
                        })),
                    },
                    options: {
                        maintainAspectRatio: false, animation: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: timeAxis(stats.duration),
                            y: { beginAtZero: true, ticks: { callback: v => v + ' ms' } },
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
                            tooltip: {
                                itemSort: (a, b) => b.parsed.y - a.parsed.y,
                                callbacks: {
                                    title: items => time(items[0].parsed.x),
                                    label: item => ' ' + item.dataset.label + ': ' + item.parsed.y + ' ms',
                                },
                            },
                        },
                    },
                });
            }
        })();
    </script>
@endif
