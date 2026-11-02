{{--
    Statistics read out of the game's replay: see ReplayStatsBuilder for what each figure means.
    Only rendered when the ladder lets this viewer download its replays. In a team game one toggle
    at the top switches every section between the players and their teams.
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

        $riSharedTeams = collect($rs['teams'])->filter(fn ($t) => count($t['members']) > 1);
        $riTeamGame = $riSharedTeams->isNotEmpty();
        $riTeamName = fn ($team) => collect($team['members'])->map($riName)->join(' & ');
        $riTeamBadge = fn ($team) => match ($team['result']) {
            'won' => ['ri-badge-won', 'Won'],
            'defeated' => ['ri-badge-lost', 'Defeated'],
            default => null,
        };

        // Contribution: within each team by player, and between the teams.
        $riMeasures = [
            'income' => 'Income',
            'spent' => 'Spent',
            'peakArmy' => 'Peak army value',
            'unitsBuilt' => 'Units built',
            'unitsKilled' => 'Units killed',
            'buildingsKilled' => 'Buildings killed',
            'unitsLost' => 'Units lost',
        ];
        $riTeamMeasures = [
            'Income' => fn ($team) => $team['totals']['income'],
            'Spent' => fn ($team) => $team['totals']['spent'],
            'Value built' => fn ($team) => array_sum($team['valueBuilt']),
            'Units built' => fn ($team) => $team['totals']['unitsBuilt'],
            'Buildings built' => fn ($team) => $team['totals']['buildingsBuilt'],
            'Kills' => fn ($team) => $team['totals']['unitsKilled'] + $team['totals']['buildingsKilled'],
            'Losses' => fn ($team) => $team['totals']['unitsLost'] + $team['totals']['buildingsLost'],
        ];

        // Tech timings: the key buildings anyone placed, and the earliest of each.
        $riTechKeys = collect(\App\Http\Services\Replay\ReplayStatsBuilder::KEY_BUILDING_NAMES)
            ->filter(fn ($label, $key) => collect($rs['players'])->contains(fn ($p) => isset($p['tech'][$key])));
        $riFastest = $riTechKeys->map(fn ($label, $key) => collect($rs['players'])->map(fn ($p) => $p['tech'][$key]['t'] ?? null)->filter(fn ($t) => $t !== null)->min());
    @endphp

    <section class="replay-insights" data-ri-mode="players">
        <div class="container-xl">
            <div class="ri-header {{ $riTeamGame ? 'ri-header-sticky' : '' }}">
                <h3>Replay statistics</h3>
                @if ($riTeamGame)
                    <div class="ri-tabs ri-mode-toggle" role="tablist">
                        <button type="button" class="ri-tab active" data-mode="players">Players</button>
                        <button type="button" class="ri-tab" data-mode="teams">Teams</button>
                    </div>
                @endif
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

            @if ($riTeamGame)
                <div class="ri-card ri-teams-only">
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
                </div>
            @endif

            @if (!empty($rs['momentum']))
                <div class="ri-card">
                    <div class="ri-card-title">Momentum</div>
                    <div class="ri-momentum-legend">
                        @foreach ($rs['teams'] as $k => $team)
                            <span class="ri-legend-item">
                                <span class="ri-dot ri-colour-{{ $riColour($team['members'][0]) }}"></span>
                                {{ $riTeamName($team) }}
                                @if ($team['won'])
                                    <span class="ri-trophy">(won)</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                    <div class="ri-chart ri-chart-momentum"><canvas id="riMomentum"></canvas></div>
                </div>
            @endif

            <div class="ri-card">
                <div class="ri-card-title">Timeline</div>
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
                    <div class="table-responsive ri-players-only">
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
                    @if ($riTeamGame)
                        <div class="table-responsive ri-teams-only">
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

            @if ($riTeamGame)
                <div class="ri-card">
                    <div class="ri-card-title">Contribution</div>

                    {{-- Players: each team's totals split between its players. --}}
                    <div class="ri-players-only">
                        @foreach ($riSharedTeams as $team)
                            <div class="ri-carry-team">
                                <div class="ri-carry-team-name">
                                    {{ $riTeamName($team) }}
                                    @if ($team['won'])
                                        <span class="ri-trophy">(won)</span>
                                    @endif
                                </div>
                                @foreach ($riMeasures as $measure => $label)
                                    @include('ladders.game._replay-share-bar', [
                                        'label' => $label,
                                        'parts' => collect($team['members'])->map(fn ($h) => [
                                            'colour' => $riColour($h),
                                            'name'   => $riName($h),
                                            'value'  => max(0, $riPlayers[$h]['totals'][$measure]),
                                        ]),
                                    ])
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    {{-- Teams: the game's totals split between the teams. --}}
                    <div class="ri-teams-only">
                        @foreach ($riTeamMeasures as $label => $value)
                            @include('ladders.game._replay-share-bar', [
                                'label' => $label,
                                'parts' => collect($rs['teams'])->map(fn ($team) => [
                                    'colour' => $riColour($team['members'][0]),
                                    'name'   => $riTeamName($team),
                                    'value'  => max(0, $value($team)),
                                ]),
                            ])
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="ri-card">
                <div class="ri-card-title">Income and value built</div>
                <div class="ri-grid-2">
                    <div class="ri-chart ri-chart-income"><canvas id="riIncome"></canvas></div>
                    <div class="ri-chart ri-chart-income"><canvas id="riValue"></canvas></div>
                </div>
            </div>

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

            <div class="ri-players ri-players-only">
                @foreach ($rs['players'] as $p)
                    <div class="ri-player ri-colour-{{ $p['color'] ?? 'none' }}">
                        <div class="ri-player-header">
                            <span class="ri-player-name">{{ $p['name'] }}</span>
                            <span class="ri-muted">{{ $p['country'] }}</span>
                            @if (($badge = $riResultBadge($p)) !== null)
                                <span class="ri-badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                            @endif
                        </div>

                        <div class="ri-player-stats">
                            <div><span>{{ number_format($p['totals']['income']) }}</span>earned</div>
                            <div><span>{{ number_format($p['totals']['spent']) }}</span>spent</div>
                            <div><span>{{ number_format($p['totals']['peakArmy']) }}</span>peak army</div>
                            <div><span>{{ number_format($p['totals']['unitsBuilt']) }}</span>units built</div>
                            <div><span>{{ number_format($p['totals']['buildingsBuilt']) }}</span>buildings built</div>
                            <div><span>{{ $p['totals']['unitsKilled'] + $p['totals']['buildingsKilled'] }}</span>destroyed</div>
                            <div><span>{{ $p['totals']['unitsLost'] + $p['totals']['buildingsLost'] }}</span>lost</div>
                        </div>

                        @if (count($p['buildOrder']) > 0)
                            <div class="ri-section-title">Build order</div>
                            <div class="ri-cameo-strip ri-build-order">
                                @foreach ($p['buildOrder'] as $b)
                                    <div class="ri-cameo-item" title="{{ $b['name'] }} placed at {{ $riTime($b['t']) }}, frame {{ number_format($b['f']) }}">
                                        <div class="ri-cameo yr-cameo cameo-{{ $b['cameo'] }}"></div>
                                        <span class="ri-cameo-label">{{ $riTime($b['t']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @foreach (['built' => 'Built most', 'killed' => 'Destroyed most', 'lost' => 'Lost most'] as $list => $title)
                            @if (count($p[$list]) > 0)
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
            const section = document.querySelector('.replay-insights');
            const palette = {
                yellow: '#ffe14d', red: '#ff4545', orange: '#ffa31a', pink: '#ff5cc0',
                purple: '#b65cff', blue: '#4d86ff', teal: '#1fe0ef', green: '#3cff6e',
            };
            const neutral = '#9aa4b2';
            const players = stats.players;
            const teams = stats.teams;
            const colourOf = house => {
                const p = players.find(x => x.house === house);
                return (p && palette[p.color]) || neutral;
            };
            const nameOf = house => (players.find(x => x.house === house) || {}).name || ('Player ' + house);
            const teamName = team => team.members.map(nameOf).join(' & ');
            const time = s => Math.floor(s / 60) + ':' + String(Math.round(s % 60)).padStart(2, '0');
            const thousands = v => (Math.abs(v) >= 1000 ? (v / 1000) + 'k' : v);
            const series = (metric, house) => (stats.timeline.series[metric] || {})[house] || [];
            const teamSeries = (metric, k) => ((stats.timeline.teamSeries || {})[metric] || {})[k] || [];
            let mode = 'players';

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
                        datasets: teams.map((team, k) => ({
                            label: teamName(team),
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

            // Timeline: one line per player, or per team with its members added together.
            const line = (label, colour, values) => ({
                label: label,
                data: values.map((v, i) => ({ x: stats.timeline.t[i], y: v })),
                borderColor: colour, backgroundColor: colour,
                pointRadius: 0, borderWidth: 2, tension: 0.15,
            });
            const datasetsFor = metric => mode === 'teams'
                ? teams.map((team, k) => line(teamName(team), colourOf(team.members[0]), teamSeries(metric, k)))
                : players.map(p => line(p.name, colourOf(p.house), series(metric, p.house)));
            const credits = ['army', 'base', 'incomeRate', 'spent', 'cash', 'score'];
            const signed = ['power', 'trade'];
            let metric = 'army';
            const timeline = new Chart(document.getElementById('riTimeline'), {
                type: 'line',
                data: { datasets: datasetsFor(metric) },
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
                timeline.data.datasets = datasetsFor(metric);
                timeline.options.scales.y.ticks.callback = credits.includes(metric) ? thousands : v => v;
                timeline.options.scales.y.beginAtZero = !signed.includes(metric);
                timeline.update();
            };
            section.querySelectorAll('.ri-tab[data-metric]').forEach(button => {
                button.addEventListener('click', () => {
                    section.querySelectorAll('.ri-tab[data-metric]').forEach(b => b.classList.remove('active'));
                    button.classList.add('active');
                    metric = button.dataset.metric;
                    redrawTimeline();
                });
            });

            // Income by source and value built by kind: one stacked bar per player, or per team.
            const sourceColours = {
                Harvested: '#e8c547', Buildings: '#5fb0ff', Crates: '#b18cff', Sold: '#9aa4b2',
                Grinding: '#ff6b6b', Stolen: '#3fe0a0', Bounty: '#ff9f43', Superweapon: '#ff5cc0',
                Warhead: '#c0c0c0', Other: '#6c757d', Unclassified: '#495057',
            };
            const sourceNames = { Buildings: 'Oil derricks & buildings', Sold: 'Selling', Harvested: 'Harvesting' };
            const sources = [...new Set(players.flatMap(p => Object.keys(p.incomeSources)))];
            const kinds = { infantry: ['Infantry', '#9fe3b2'], vehicle: ['Vehicles', '#ffd36b'], aircraft: ['Aircraft', '#b18cff'], building: ['Buildings', '#5fb0ff'] };
            const sumOf = (team, pick) => team.members.reduce((sum, house) => sum + pick(players.find(p => p.house === house)), 0);
            const barLabels = () => mode === 'teams' ? teams.map(teamName) : players.map(p => p.name);
            const barValues = pick => mode === 'teams' ? teams.map(team => sumOf(team, pick)) : players.map(pick);
            const incomeData = () => sources.map(source => ({
                label: sourceNames[source] || source,
                data: barValues(p => p.incomeSources[source] || 0),
                backgroundColor: sourceColours[source] || '#6c757d',
                borderRadius: 3,
            }));
            const valueData = () => Object.entries(kinds).map(([kind, [label, colour]]) => ({
                label: label,
                data: barValues(p => p.valueBuilt[kind] || 0),
                backgroundColor: colour,
                borderRadius: 3,
            }));
            const stackedBars = (id, title, datasets) => new Chart(document.getElementById(id), {
                type: 'bar',
                data: { labels: barLabels(), datasets: datasets },
                options: {
                    indexAxis: 'y', maintainAspectRatio: false, animation: false,
                    scales: {
                        x: { stacked: true, ticks: { callback: thousands } },
                        y: { stacked: true, grid: { display: false } },
                    },
                    plugins: {
                        title: { display: true, text: title, align: 'start', color: '#fff', font: { weight: 'normal' } },
                        legend: { position: 'bottom', labels: { boxWidth: 12 } },
                        tooltip: { callbacks: { label: item => ' ' + item.dataset.label + ': ' + item.parsed.x.toLocaleString() } },
                    },
                },
            });
            const incomeChart = stackedBars('riIncome', 'Income', incomeData());
            const valueChart = stackedBars('riValue', 'Value built', valueData());
            const redrawBars = () => {
                [[incomeChart, incomeData()], [valueChart, valueData()]].forEach(([chart, datasets]) => {
                    chart.data.labels = barLabels();
                    chart.data.datasets.forEach((d, i) => { d.data = datasets[i].data; });
                    chart.update();
                });
            };

            // The Players / Teams toggle: the mode attribute shows one form of each section, and the
            // charts redraw with the other set of lines and bars.
            section.querySelectorAll('.ri-tab[data-mode]').forEach(button => {
                button.addEventListener('click', () => {
                    section.querySelectorAll('.ri-tab[data-mode]').forEach(b => b.classList.remove('active'));
                    button.classList.add('active');
                    mode = button.dataset.mode;
                    section.dataset.riMode = mode;
                    redrawTimeline();
                    redrawBars();
                });
            });

            // The sticky header sits below the site's fixed navbar, and moves up to the top edge
            // while the navbar is hidden by scrolling down.
            const navbar = document.querySelector('.js-navbar');
            if (navbar && section.querySelector('.ri-header-sticky')) {
                const place = () => section.style.setProperty('--ri-sticky-top',
                    (navbar.classList.contains('scrolling') ? 0 : navbar.offsetHeight) + 'px');
                new MutationObserver(place).observe(navbar, { attributes: true, attributeFilter: ['class'] });
                window.addEventListener('resize', place);
                place();
            }

            // Connection: each player's reported response time.
            if (stats.connection && document.getElementById('riConnection')) {
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
