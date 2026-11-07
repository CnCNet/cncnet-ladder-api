<?php

namespace App\Http\Services\Replay;

/**
 * Turns one replay into the statistics the game page shows: how each player's army and economy
 * moved over the game, what they built and when, who was ahead when, how each player's connection
 * held up, and whether the game went out of sync.
 *
 * Everything comes from what the spawner recorded - the per-house samples it writes every 60
 * frames, the payments it saw, the players' own orders, the network's timing events and the summary
 * it closes the file with. The calculations follow yrrp-analyser so the two tools agree.
 */
class ReplayStatsBuilder
{
    /** Bumped whenever the output changes shape or meaning, so stored stats can be rebuilt. */
    public const VERSION = 9;

    private const INCOME_RATE_WINDOW_SECONDS = 30;
    private const MAX_CHART_POINTS = 240;
    private const BUILD_ORDER_LENGTH = 24;
    // A placement the game refused is followed by another try; nothing can be placed twice this
    // soon, so a repeat of the same building within this long replaces the attempt before it.
    private const PLACEMENT_RETRY_SECONDS = 5;
    private const TOP_TYPES = 8;
    // Every building placement is kept for the tech timings; this bounds a long game.
    private const MAX_PLACEMENTS = 600;

    // The buildings whose placement time says how fast a player teched, by type ID across the
    // three sides, and the names the table shows them under.
    private const KEY_BUILDINGS = [
        'barracks'    => ['GAPILE', 'NAHAND', 'YABRCK'],
        'refinery'    => ['GAREFN', 'NAREFN', 'YAREFN'],
        'warFactory'  => ['GAWEAP', 'NAWEAP', 'YAWEAP'],
        'radar'       => ['GAAIRC', 'AMRADR', 'NARADR', 'YAPSYR'],
        'navalYard'   => ['GAYARD', 'NAYARD', 'YAYARD'],
        'battleLab'   => ['GATECH', 'NATECH', 'YATECH'],
        'superweapon' => ['GAWEAT', 'GACSPH', 'NAMISL', 'NAIRON', 'YAPPET', 'YAGNTC'],
        'defence'     => ['GAPILL', 'NALASR', 'YAGGUN', 'ATESLA', 'NASAM', 'NAFLAK', 'GTGCAN', 'YAPSYT', 'CAPRIS', 'GAPRIS'],
    ];
    public const KEY_BUILDING_NAMES = [
        'barracks' => 'Barracks', 'refinery' => 'Refinery', 'warFactory' => 'War Factory', 'radar' => 'Radar',
        'navalYard' => 'Naval Yard', 'battleLab' => 'Battle Lab', 'superweapon' => 'Superweapon', 'defence' => 'First defence',
    ];

    // AbstractType values, and the type list each kind's objects are counted against.
    private const TYPE_LIST_OF = [
        1 => 40, 40 => 40,   // Unit, UnitType
        2 => 3, 3 => 3,      // Aircraft, AircraftType
        6 => 7, 7 => 7,      // Building, BuildingType
        15 => 16, 16 => 16,  // Infantry, InfantryType
    ];
    private const KIND_NAMES = [40 => 'vehicle', 3 => 'aircraft', 7 => 'building', 16 => 'infantry'];

    // The summary's per-type count arrays, in StatisticsHouseArray order, with the type list each indexes.
    private const ARRAY_BUILT = [0 => 3, 1 => 16, 2 => 40, 3 => 7];
    private const ARRAY_KILLED = [4 => 3, 5 => 16, 6 => 40, 7 => 7];
    private const ARRAY_LOST = [14 => 3, 15 => 16, 16 => 40, 17 => 7];

    private const EVENT_PLACE = 0x0B;
    private const EVENT_SPECIAL_PLACE = 0x12;
    private const EVENT_FRAMEINFO = 0x1C;

    // ResponseTime2 carries the peer's response time as ticks + 1, at 16 ms a tick.
    private const SYSTEM_TICK_MS = 16;

    // The connection chart's resolution: response times swing every few seconds, so each point is
    // the worst of a bucket at least this long, and a long game gets longer buckets.
    private const CONNECTION_BUCKET_SECONDS = 10;
    private const MAX_CONNECTION_POINTS = 120;

    // ResponseTime2 is a signed byte of ticks, so a response time this long or longer reads as this.
    private const MAX_RESPONSE_TICKS = 127;
    // [SuperWeaponTypes] as Yuri's Revenge ships it. The replay does not carry the list, so a map
    // that reorders it would be mislabelled - rare enough on ladder maps to accept.
    private const SUPERWEAPONS = [
        'Nuclear Missile', 'Iron Curtain', 'Weather Storm', 'Chronosphere', 'Chronosphere',
        'Soviet Paradrop', 'American Paradrop', 'Psychic Dominator', 'Spy Plane',
        'Genetic Mutator', 'Force Shield', 'Psychic Reveal',
    ];
    // The launches worth a mark on the charts: Nuclear Missile, Iron Curtain, Weather Storm,
    // Chronosphere, Psychic Dominator and Genetic Mutator. Index 4 is the Chronosphere's second click.
    private const MARKED_SUPERWEAPONS = [0, 1, 2, 3, 7, 9];

    // [Countries] IDs as the lobby names them.
    private const COUNTRIES = [
        'Americans' => 'America', 'Alliance' => 'Korea', 'French' => 'France', 'Germans' => 'Germany',
        'British' => 'Great Britain', 'Africans' => 'Libya', 'Arabs' => 'Iraq', 'Confederation' => 'Cuba',
        'Russians' => 'Russia', 'YuriCountry' => 'Yuri',
    ];

    private const COLOURS = [
        3 => 'yellow', 11 => 'red', 13 => 'orange', 15 => 'pink',
        17 => 'purple', 21 => 'blue', 25 => 'teal', 29 => 'green',
    ];

    // Win likelihood: yrrp-analyser's WinLikelihood, itself the Replay Insights advantage index.
    private const WIN_WEIGHTS = [0.55, 0.25, 0.15, 0.05];
    private const WIN_SHARPNESS = 6;
    private const WIN_EVEN_SHARE_BLEND = 0.15;
    private const WIN_SMOOTHING_SECONDS = 12;
    private const WIN_OPENING_START = 30;
    private const WIN_OPENING_FULL = 90;
    private const WIN_INCOME_WINDOW = 60;
    private const WIN_CASH_ALLOWANCE = 2000;

    // A comeback: the eventual winners were down to this share after the opening had played out.
    private const COMEBACK_BELOW = 0.25;
    private const COMEBACK_AFTER_SECONDS = 180;

    /**
     * Null when the replay has no statistics - a recording made before the spawner wrote them, or
     * one that never closed cleanly.
     */
    public function build(string $path): ?array
    {
        $reader = new ReplayFileReader($path);
        $header = $reader->header();
        $summary = $reader->statistics();

        if ($summary === null || count($summary['houses']) === 0)
        {
            return null;
        }

        $classifier = new IncomeClassifier($summary['modules']);
        $types = $summary['types'];

        // Game time: the recorded speed, changed wherever a frame record says so.
        $speedSegments = [[0, 0.0, ReplayFileReader::framesPerSecond($header['gameSpeed'])]];
        $secondsAt = function (int $frame) use (&$speedSegments): float
        {
            $segment = $speedSegments[0];
            foreach ($speedSegments as $s)
            {
                if ($s[0] > $frame)
                {
                    break;
                }
                $segment = $s;
            }
            return $segment[1] + ($frame - $segment[0]) / $segment[2];
        };

        $timelines = [];
        $running = [];
        $atFirst = [];
        $buildOrders = [];
        $superweapons = [];
        $connection = [];
        $outOfSync = ['mismatches' => [], 'lastMatch' => []];

        $walk = $reader->walkFrames(function (int $frame, array $record) use (
            &$speedSegments, $secondsAt, $classifier,
            &$timelines, &$running, &$atFirst, &$buildOrders, &$superweapons, &$connection, &$outOfSync)
        {
            if (isset($record['gameSpeed']))
            {
                $last = end($speedSegments);
                $fps = ReplayFileReader::framesPerSecond($record['gameSpeed']);
                if ($fps !== $last[2])
                {
                    $speedSegments[] = [$frame, $secondsAt($frame), $fps];
                }
            }

            // Payments first: a frame's samples already include its payments.
            foreach ($record['moneyIn'] ?? [] as $payment)
            {
                $source = $classifier->classify($payment['caller']);
                $running[$payment['house']][$source] = ($running[$payment['house']][$source] ?? 0) + $payment['amount'];
            }

            foreach ($record['houseStats'] ?? [] as $s)
            {
                $this->addSample($timelines, $running, $atFirst, $frame, $secondsAt($frame), $s);
            }

            foreach ($record['events'] ?? [] as $event)
            {
                $this->addEvent($buildOrders, $superweapons, $connection, $outOfSync, $frame, $secondsAt($frame), $event);
            }
        });

        if (count($timelines) === 0)
        {
            return null;
        }

        $lastFrame = max(1, $walk['lastFrame']);
        $duration = $secondsAt($lastFrame);

        $players = $this->players($summary, $timelines, $running, $buildOrders, $types);
        if (count($players) === 0)
        {
            return null;
        }

        $houses = array_column($players, 'house');
        $teams = $this->teams($summary, $players);
        foreach ($teams as $index => $team)
        {
            foreach ($team['members'] as $house)
            {
                $player = array_search($house, $houses, true);
                $players[$player]['team'] = $index;
                $players[$player]['won'] = $team['won'];
                if ($team['won'] && $players[$player]['result'] === 'survived')
                {
                    $players[$player]['result'] = 'won';
                }
            }
        }

        $teams = $this->teamTotals($teams, $players);

        $recorder = null;
        foreach ($summary['houses'] as $h)
        {
            if (($h['flags'] & ReplayFileReader::HOUSE_RECORDING_PLAYER) && in_array($h['house'], $houses, true))
            {
                $recorder = $h['house'];
            }
        }

        $stats = [
            'version'   => self::VERSION,
            'duration'  => (int) round($duration),
            'recorder'  => $recorder,
            'players'   => $players,
            'teams'     => $teams,
            'timeline'  => $this->chartTimeline($timelines, $houses, $teams),
            'momentum'  => $this->momentum($timelines, $teams),
            'events'     => $this->gameEvents($players, $superweapons),
            'connection' => $this->connection($connection, $houses, $duration),
            'sync'       => $this->sync($summary, $outOfSync, $houses, $secondsAt),
        ];
        $stats['highlights'] = $this->highlights($stats);

        return $stats;
    }

    private function addSample(array &$timelines, array $running, array &$atFirst, int $frame, float $t, array $s): void
    {
        $house = $s['house'];
        $totals = $running[$house] ?? [];
        $refunded = $totals[IncomeClassifier::REFUNDED] ?? 0;
        $starting = $totals[IncomeClassifier::STARTING_CREDITS] ?? 0;
        $cash = $s['credits'] + $s['storedOre'];

        if (!isset($timelines[$house]))
        {
            $timelines[$house] = ['first' => $s, 'samples' => [], 'defeatedAt' => null];
            $atFirst[$house] = [$refunded, $starting];
        }

        $first = $timelines[$house]['first'];
        [$firstRefunded, $firstStarting] = $atFirst[$house];

        // Money in = what the house holds now plus what it has spent, less spending that came back
        // and money it was simply handed at the start.
        $income = $cash - ($first['credits'] + $first['storedOre'])
            + $s['creditsSpent'] - $first['creditsSpent']
            - ($refunded - $firstRefunded)
            - ($starting - $firstStarting);

        $timelines[$house]['samples'][] = [
            'f'        => $frame,
            't'        => $t,
            'cash'     => $cash,
            'income'   => $income,
            'spent'    => $s['creditsSpent'] - $refunded,
            'army'     => $s['armyValue'],
            'base'     => $s['buildingValue'],
            'armySize' => $s['units'] + $s['infantry'] + $s['aircraft'],
            'buildings' => $s['buildings'],
            'power'    => $s['powerOutput'] - $s['powerDrain'],
            'kills'    => $s['unitsKilled'] + $s['buildingsKilled'],
            'losses'   => $s['unitsLost'] + $s['buildingsLost'],
            'trade'    => ($s['unitsKilled'] + $s['buildingsKilled']) - ($s['unitsLost'] + $s['buildingsLost']),
            'score'    => $s['score'],
            'flags'    => $s['flags'],
        ];

        if (($s['flags'] & ReplayFileReader::HOUSE_DEFEATED) && $timelines[$house]['defeatedAt'] === null)
        {
            $timelines[$house]['defeatedAt'] = $t;
        }
    }

    private function addEvent(array &$buildOrders, array &$superweapons, array &$connection, array &$outOfSync,
        int $frame, float $t, string $event): void
    {
        $type = ord($event[0]);
        $house = ord($event[2]);
        if ($house >= 128)
        {
            return;
        }

        $data = ReplayFileReader::EVENT_DATA_OFFSET;

        // Every peer reports its own response time, so this covers every player, not only the one
        // who recorded. Zero means no measurement; a negative byte has overflowed.
        if ($type === ReplayFileReader::EVENT_RESPONSE_TIME2)
        {
            $ticks = ord($event[$data]);
            if ($ticks > 0 && $ticks <= self::MAX_RESPONSE_TICKS)
            {
                $connection[$house][] = [$t, ($ticks - 1) * self::SYSTEM_TICK_MS];
            }
            return;
        }

        // A peer whose hash of a frame was not the recorder's: the game came apart there, unless the
        // same peer matched again later.
        if ($type === self::EVENT_FRAMEINFO)
        {
            $check = ord($event[$data]);
            if ($check === ReplayFileReader::FRAMEINFO_MISMATCH)
            {
                $outOfSync['mismatches'][] = [$frame, $house];
            }
            elseif ($check === ReplayFileReader::FRAMEINFO_MATCH)
            {
                $outOfSync['lastMatch'][$house] = $frame;
            }
            return;
        }

        // A building going down: PLACE with the type's heap index. The same event with a heap index
        // of -1 is the engine letting a finished unit out of its factory, named by category only,
        // so units cannot be listed by type from the recording.
        if ($type === self::EVENT_PLACE && count($buildOrders[$house] ?? []) < self::MAX_PLACEMENTS)
        {
            $list = self::TYPE_LIST_OF[ReplayFileReader::u32($event, $data)] ?? null;
            $index = ReplayFileReader::i32($event, $data + 4);
            if ($list === 7 && $index >= 0)
            {
                $placed = $buildOrders[$house] ?? [];
                $previous = count($placed) > 0 ? $placed[count($placed) - 1] : null;
                if ($previous !== null && $previous[2] === $list && $previous[3] === $index
                    && $t - $previous[0] <= self::PLACEMENT_RETRY_SECONDS)
                {
                    array_pop($buildOrders[$house]);
                }
                $buildOrders[$house][] = [$t, $frame, $list, $index];
            }
        }
        elseif ($type === self::EVENT_SPECIAL_PLACE)
        {
            $superweapons[] = [$t, $house, ReplayFileReader::i32($event, $data)];
        }
    }

    /** How the player's game ended, from the flags the recording closed with. */
    private function result(int $flags, bool $defeated): string
    {
        if ($flags & ReplayFileReader::HOUSE_WINNER)
        {
            return 'won';
        }
        if ($flags & ReplayFileReader::HOUSE_RESIGNED)
        {
            return 'resigned';
        }
        if ($flags & ReplayFileReader::HOUSE_LOST_CONNECTION)
        {
            return 'disconnected';
        }
        if ($defeated || ($flags & ReplayFileReader::HOUSE_DEFEATED))
        {
            return 'defeated';
        }
        return 'survived';
    }

    private function players(array $summary, array $timelines, array $running, array $buildOrders, array $types): array
    {
        $players = [];
        foreach ($summary['houses'] as $h)
        {
            $house = $h['house'];
            $timeline = $timelines[$house] ?? null;

            // Spectators are houses too; they start the game as observers and own nothing.
            if ($timeline === null || ($timeline['first']['flags'] & ReplayFileReader::HOUSE_OBSERVER))
            {
                continue;
            }

            $samples = $timeline['samples'];
            $last = end($samples);

            $sources = [];
            foreach ($running[$house] ?? [] as $source => $amount)
            {
                if (IncomeClassifier::isIncome($source) && $amount > 0)
                {
                    $sources[$source] = $amount;
                }
            }
            arsort($sources);

            $players[] = [
                'house'      => $house,
                'name'       => $h['name'],
                'country'    => self::COUNTRIES[$h['country']] ?? $h['country'],
                'color'      => self::COLOURS[$h['color']] ?? null,
                'team'       => null,
                'won'        => ($h['flags'] & ReplayFileReader::HOUSE_WINNER) !== 0,
                'result'     => $this->result($h['flags'], $timeline['defeatedAt'] !== null),
                'defeatedAt' => $timeline['defeatedAt'] !== null ? (int) round($timeline['defeatedAt']) : null,
                'totals'     => [
                    'income'          => (int) $last['income'],
                    'spent'           => (int) $last['spent'],
                    'peakArmy'        => (int) max(array_column($samples, 'army')),
                    'unitsKilled'     => array_sum($h['unitsKilledOfHouse']),
                    'buildingsKilled' => array_sum($h['buildingsKilledOfHouse']),
                    'unitsLost'       => $h['unitsLost'],
                    'buildingsLost'   => $h['buildingsLost'],
                    'score'           => $h['score'],
                    'unitsBuilt'      => $this->total($h['arrays'], [0, 1, 2]),
                    'buildingsBuilt'  => $this->total($h['arrays'], [3]),
                ],
                'incomeSources' => $sources,
                'valueBuilt'    => $this->valueBuilt($h['arrays'], $types),
                'buildOrder'    => $this->buildOrder($buildOrders[$house] ?? [], $types),
                'tech'          => $this->techTimes($buildOrders[$house] ?? [], $types),
                'built'         => $this->topTypes($h['arrays'], self::ARRAY_BUILT, $types),
                'killed'        => $this->topTypes($h['arrays'], self::ARRAY_KILLED, $types),
                'lost'          => $this->topTypes($h['arrays'], self::ARRAY_LOST, $types),
            ];
        }

        return $players;
    }

    /** The first buildings each player placed, in order, with the game time and frame of each. */
    private function buildOrder(array $orders, array $types): array
    {
        $entries = [];
        foreach (array_slice($orders, 0, self::BUILD_ORDER_LENGTH) as [$t, $frame, $list, $index])
        {
            $entries[] = $this->describeType($types[$list][$index] ?? null, $list, $index)
                + ['t' => (int) round($t), 'f' => $frame];
        }
        return $entries;
    }

    /** When each key building first went down, by the type IDs in KEY_BUILDINGS. */
    private function techTimes(array $placements, array $types): array
    {
        $times = [];
        foreach ($placements as [$t, $frame, $list, $index])
        {
            $type = $types[$list][$index] ?? null;
            $id = strtoupper($type['id'] ?? '');
            foreach (self::KEY_BUILDINGS as $key => $ids)
            {
                if (!isset($times[$key]) && in_array($id, $ids, true))
                {
                    $times[$key] = ['t' => (int) round($t), 'name' => $this->describeType($type, $list, $index)['name']];
                }
            }
        }
        return $times;
    }

    /**
     * Credits' worth of everything that joined the house, by kind: built counts times the type's
     * cost. Starting units, free units and captures are in the built counts, so this is value
     * owned over the game rather than money spent.
     */
    private function valueBuilt(array $arrays, array $types): array
    {
        $out = [];
        foreach (self::ARRAY_BUILT as $array => $list)
        {
            $credits = 0;
            foreach ($arrays[$array] ?? [] as $index => $count)
            {
                $credits += $count * max(0, $types[$list][$index]['cost'] ?? 0);
            }
            $out[self::KIND_NAMES[$list]] = $credits;
        }
        return $out;
    }

    private function topTypes(array $arrays, array $which, array $types): array
    {
        $counts = [];
        foreach ($which as $array => $list)
        {
            foreach ($arrays[$array] ?? [] as $index => $count)
            {
                if ($count > 0)
                {
                    $counts[] = $this->describeType($types[$list][$index] ?? null, $list, $index) + ['count' => $count];
                }
            }
        }

        usort($counts, fn ($a, $b) => $b['count'] <=> $a['count']);
        return array_slice($counts, 0, self::TOP_TYPES);
    }

    /** Everything counted in some of the summary's per-type arrays. */
    private function total(array $arrays, array $which): int
    {
        $total = 0;
        foreach ($which as $array)
        {
            $total += array_sum($arrays[$array] ?? []);
        }
        return $total;
    }

    private function describeType(?array $type, int $list, int $index): array
    {
        $name = $type['name'] ?? '';
        if ($name === '' || str_starts_with($name, 'MISSING:'))
        {
            $name = $type['id'] ?? "Unknown {$index}";
        }

        return [
            'id'    => $type['id'] ?? "{$list}#{$index}",
            'name'  => $name,
            'cameo' => strtolower($type['cameo'] ?? ''),
            'kind'  => self::KIND_NAMES[$list] ?? '',
        ];
    }

    /**
     * Teams from the alliances held at the end of the game, counting only mutual ones; with none,
     * everyone plays alone.
     */
    private function teams(array $summary, array $players): array
    {
        $houses = array_column($players, 'house');
        $allies = [];
        foreach ($summary['houses'] as $h)
        {
            $allies[$h['house']] = $h['allies'];
        }

        $parent = array_combine($houses, $houses);
        $find = function (int $h) use (&$parent): int
        {
            while ($parent[$h] !== $h)
            {
                $h = $parent[$h] = $parent[$parent[$h]];
            }
            return $h;
        };

        foreach ($houses as $a)
        {
            foreach ($houses as $b)
            {
                if ($a < $b && $a < 32 && $b < 32
                    && (($allies[$a] ?? 0) & (1 << $b)) && (($allies[$b] ?? 0) & (1 << $a)))
                {
                    $ra = $find($a);
                    $rb = $find($b);
                    if ($ra !== $rb)
                    {
                        $parent[max($ra, $rb)] = min($ra, $rb);
                    }
                }
            }
        }

        $groups = [];
        foreach ($houses as $h)
        {
            $groups[$find($h)][] = $h;
        }
        ksort($groups);

        $teams = [];
        foreach (array_values($groups) as $members)
        {
            sort($members);
            $flaggedWinner = false;
            $eliminated = true;
            foreach ($players as $p)
            {
                if (in_array($p['house'], $members, true))
                {
                    $flaggedWinner = $flaggedWinner || $p['won'];
                    $eliminated = $eliminated && $p['defeatedAt'] !== null;
                }
            }
            $teams[] = ['members' => $members, 'won' => $flaggedWinner, 'eliminated' => $eliminated];
        }

        // A recording that stops the moment the last opponent falls carries no winner flag; the
        // one team still standing is the winner then.
        if (count($teams) > 1 && count(array_filter($teams, fn ($t) => $t['won'])) === 0)
        {
            $standing = array_keys(array_filter($teams, fn ($t) => !$t['eliminated']));
            if (count($standing) === 1)
            {
                $teams[$standing[0]]['won'] = true;
            }
        }

        foreach ($teams as &$team)
        {
            unset($team['eliminated']);
        }

        return $teams;
    }

    /**
     * What each team did together: its members' totals and value built added up, how it finished,
     * and the earliest of its members' tech timings.
     */
    private function teamTotals(array $teams, array $players): array
    {
        $byHouse = array_column($players, null, 'house');
        $summed = ['income', 'spent', 'unitsBuilt', 'buildingsBuilt', 'unitsKilled', 'buildingsKilled', 'unitsLost', 'buildingsLost', 'score'];

        foreach ($teams as &$team)
        {
            $totals = array_fill_keys($summed, 0);
            $value = [];
            $tech = [];
            $allDefeated = true;

            foreach ($team['members'] as $house)
            {
                $p = $byHouse[$house];
                foreach ($summed as $key)
                {
                    $totals[$key] += $p['totals'][$key];
                }
                foreach ($p['valueBuilt'] as $kind => $credits)
                {
                    $value[$kind] = ($value[$kind] ?? 0) + $credits;
                }
                foreach ($p['tech'] as $key => $entry)
                {
                    if (!isset($tech[$key]) || $entry['t'] < $tech[$key]['t'])
                    {
                        $tech[$key] = $entry + ['house' => $house];
                    }
                }
                $allDefeated = $allDefeated && $p['defeatedAt'] !== null;
            }

            $team['totals'] = $totals;
            $team['valueBuilt'] = $value;
            $team['tech'] = $tech;
            $team['result'] = $team['won'] ? 'won' : ($allDefeated ? 'defeated' : 'survived');
        }
        unset($team);

        return $teams;
    }

    /**
     * Every chart series thinned to a few hundred points on one shared time axis. Every house is
     * sampled on the same frames, so one index serves all of them. Each team's series is the sum of
     * its members'.
     */
    private function chartTimeline(array $timelines, array $houses, array $teams): array
    {
        $reference = $timelines[$houses[0]]['samples'];
        $count = count($reference);
        $step = max(1, (int) ceil($count / self::MAX_CHART_POINTS));

        $indices = range(0, $count - 1, $step);
        if (end($indices) !== $count - 1)
        {
            $indices[] = $count - 1;
        }

        $series = ['army' => [], 'base' => [], 'cash' => [], 'income' => [], 'incomeRate' => [],
            'spent' => [], 'armySize' => [], 'buildings' => [], 'power' => [], 'kills' => [], 'losses' => [],
            'trade' => [], 'score' => []];

        foreach ($houses as $house)
        {
            $samples = $timelines[$house]['samples'];
            $rates = $this->rates($samples);

            foreach (array_keys($series) as $metric)
            {
                $values = [];
                foreach ($indices as $i)
                {
                    $at = min($i, count($samples) - 1);
                    $values[] = (int) round($metric === 'incomeRate' ? $rates[$at] : $samples[$at][$metric]);
                }
                $series[$metric][$house] = $values;
            }
        }

        $teamSeries = [];
        foreach (array_keys($series) as $metric)
        {
            foreach ($teams as $k => $team)
            {
                $sum = array_fill(0, count($indices), 0);
                foreach ($team['members'] as $house)
                {
                    foreach ($series[$metric][$house] as $i => $value)
                    {
                        $sum[$i] += $value;
                    }
                }
                $teamSeries[$metric][$k] = $sum;
            }
        }

        return [
            't'          => array_map(fn ($i) => (int) round($reference[$i]['t']), $indices),
            'series'     => $series,
            'teamSeries' => $teamSeries,
        ];
    }

    /**
     * Money earned per minute over a trailing window, sample by sample. The window is never shorter
     * than its full length: the game started at zero, so early on it reaches back into nothing rather
     * than dividing a few seconds' gain by a few seconds.
     */
    private function rates(array $samples): array
    {
        $rates = [];
        $j = 0;
        $count = count($samples);
        for ($i = 0; $i < $count; $i++)
        {
            $now = $samples[$i]['t'];
            while ($j < $i && $now - $samples[$j + 1]['t'] >= self::INCOME_RATE_WINDOW_SECONDS)
            {
                $j++;
            }
            $elapsed = max(self::INCOME_RATE_WINDOW_SECONDS, $now - $samples[$j]['t']);
            $gained = $samples[$i]['income'] - $samples[$j]['income'];
            $rates[] = max(0, $gained) / $elapsed * 60;
        }
        return $rates;
    }

    /**
     * Who was ahead, moment by moment: a weighted share of each team's army value, recent income,
     * base value and usable cash, smoothed and sharpened so its percentages track how often the
     * favourite actually won. It only looks backwards, so it never knows the result early.
     */
    private function momentum(array $timelines, array $teams): ?array
    {
        if (count($teams) < 2)
        {
            return null;
        }

        $houses = array_merge(...array_column($teams, 'members'));
        $reference = $timelines[$houses[0]]['samples'];
        $count = count($reference);
        $n = count($teams);

        $times = [];
        $shares = [];
        $previous = null;
        $lastTime = 0.0;

        for ($i = 0; $i < $count; $i++)
        {
            $now = $reference[$i]['t'];
            $features = [];
            $active = [];

            foreach ($teams as $k => $team)
            {
                $army = $income = $buildings = $cash = 0.0;
                $living = 0;
                foreach ($team['members'] as $house)
                {
                    $samples = $timelines[$house]['samples'];
                    $at = min($i, count($samples) - 1);
                    $defeated = $timelines[$house]['defeatedAt'];
                    if ($defeated !== null && $defeated <= $now)
                    {
                        continue;
                    }
                    $living++;

                    // The last sample at least a window back, or the first one.
                    $old = $at;
                    while ($old > 0 && $samples[$old]['t'] > $samples[$at]['t'] - self::WIN_INCOME_WINDOW)
                    {
                        $old--;
                    }
                    $elapsed = $samples[$at]['t'] - $samples[$old]['t'];
                    $rate = $elapsed > 0 ? max(0, $samples[$at]['income'] - $samples[$old]['income']) * 60 / $elapsed : 0;

                    $army += max(0, $samples[$at]['army']);
                    $buildings += max(0, $samples[$at]['base']);
                    $income += $rate;
                    $cash += min(max(0, $samples[$at]['cash']), self::WIN_CASH_ALLOWANCE + $rate);
                }
                $features[$k] = [$army, $income, $buildings, $cash];
                $active[$k] = $living > 0;
            }

            $index = $this->winStep($features, $active, $previous, $now - $lastTime, $now);
            $previous = $index;
            $lastTime = $now;

            $times[] = $now;
            $shares[] = $this->sharpen($index);
        }

        // Thin to chart resolution.
        $step = max(1, (int) ceil($count / self::MAX_CHART_POINTS));
        $out = ['t' => [], 'shares' => array_fill(0, $n, [])];
        for ($i = 0; $i < $count; $i += $step)
        {
            $out['t'][] = (int) round($times[$i]);
            foreach ($shares[$i] as $k => $share)
            {
                $out['shares'][$k][] = round($share, 3);
            }
        }
        return $out;
    }

    private function winStep(array $features, array $active, ?array $previous, float $dt, float $time): array
    {
        $n = count($features);
        $live = array_keys(array_filter($active));
        if (count($live) === 0)
        {
            return array_fill(0, $n, 0.0);
        }

        $liveCount = count($live);
        $uniform = [];
        for ($i = 0; $i < $n; $i++)
        {
            $uniform[$i] = $active[$i] ? 1 / $liveCount : 0;
        }

        $normalised = array_fill(0, $n, array_fill(0, count(self::WIN_WEIGHTS), 0.0));
        foreach (self::WIN_WEIGHTS as $f => $_)
        {
            $total = 0.0;
            foreach ($live as $i)
            {
                $total += $features[$i][$f];
            }
            foreach ($live as $i)
            {
                $normalised[$i][$f] = $total > 0 ? $features[$i][$f] / $total : 1 / $liveCount;
            }
        }

        $evidence = max(0, min(1, ($time - self::WIN_OPENING_START) / (self::WIN_OPENING_FULL - self::WIN_OPENING_START)));
        $weight = $evidence * (1 - self::WIN_EVEN_SHARE_BLEND);

        $target = [];
        for ($i = 0; $i < $n; $i++)
        {
            $raw = 0.0;
            foreach (self::WIN_WEIGHTS as $f => $w)
            {
                $raw += $normalised[$i][$f] * $w;
            }
            $target[$i] = $weight * $raw + (1 - $weight) * $uniform[$i];
        }

        if ($liveCount === 1)
        {
            $shares = $uniform;
        }
        elseif ($previous === null)
        {
            $shares = $target;
        }
        else
        {
            $mass = 0.0;
            foreach ($live as $i)
            {
                $mass += $previous[$i];
            }
            $alpha = 1 - exp(-max(0, $dt) / self::WIN_SMOOTHING_SECONDS);
            $shares = [];
            for ($i = 0; $i < $n; $i++)
            {
                if (!$active[$i])
                {
                    $shares[$i] = 0.0;
                    continue;
                }
                $old = $mass > 0 ? $previous[$i] / $mass : $uniform[$i];
                $shares[$i] = (1 - $alpha) * $old + $alpha * $target[$i];
            }
        }

        $sum = array_sum($shares);
        return $sum > 0 ? array_map(fn ($s) => $s / $sum, $shares) : $shares;
    }

    private function sharpen(array $index): array
    {
        $powered = array_map(fn ($v) => $v > 0 ? $v ** self::WIN_SHARPNESS : 0, $index);
        $sum = array_sum($powered);
        return $sum > 0 ? array_map(fn ($v) => $v / $sum, $powered) : $index;
    }

    /**
     * Each player's response time over the game, as the worst of each bucket of game time, with its
     * median and worst overall. Null when the recording has no ResponseTime2 events at all (a game
     * against the AI).
     */
    private function connection(array $samples, array $houses, float $duration): ?array
    {
        $bucket = max(self::CONNECTION_BUCKET_SECONDS, (int) ceil($duration / self::MAX_CONNECTION_POINTS));
        $out = [];
        foreach ($houses as $house)
        {
            $series = $samples[$house] ?? [];
            if (count($series) === 0)
            {
                continue;
            }

            $values = array_column($series, 1);
            sort($values);
            // The worst of each bucket, so a spike survives; each point sits at its bucket's start.
            $worst = [];
            foreach ($series as [$t, $ms])
            {
                $b = intdiv((int) $t, $bucket);
                $worst[$b] = max($worst[$b] ?? 0, $ms);
            }
            $points = [];
            foreach ($worst as $b => $ms)
            {
                $points[] = [$b * $bucket, $ms];
            }

            $out[$house] = [
                'median' => $values[intdiv(count($values), 2)],
                'peak'   => end($values),
                'capped' => end($values) >= (self::MAX_RESPONSE_TICKS - 1) * self::SYSTEM_TICK_MS,
                'points' => $points,
            ];
        }

        return count($out) > 0 ? $out : null;
    }

    /**
     * Whether the game went out of sync, and where: the first frame a peer's hash disagreed with the
     * recorder's, and whose. Falls back to the game's own out-of-sync flag, which the recording also
     * keeps, when the network events say nothing.
     *
     * A game that comes apart stays apart, so a mismatch followed by a match from the same peer is
     * not one. Recordings made before the recorder knew about the skip-CRC timer at the start of a
     * game have exactly that: one mismatch per peer on the first check, then matches throughout.
     */
    private function sync(array $summary, array $outOfSync, array $houses, callable $secondsAt): ?array
    {
        $mismatches = array_values(array_filter($outOfSync['mismatches'],
            fn ($m) => $m[0] > ($outOfSync['lastMatch'][$m[1]] ?? -1)));

        if (count($mismatches) > 0)
        {
            $frame = $mismatches[0][0];
            $peers = [];
            foreach ($mismatches as [$f, $house])
            {
                if ($f === $frame && in_array($house, $houses, true) && !in_array($house, $peers, true))
                {
                    $peers[] = $house;
                }
            }
            return ['frame' => $frame, 't' => (int) round($secondsAt($frame)), 'peers' => $peers];
        }

        $game = $summary['game'] ?? null;
        if ($game !== null && $game['outOfSync'])
        {
            $frame = max(0, (int) $game['outOfSyncFrame']);
            return ['frame' => $frame, 't' => (int) round($secondsAt($frame)), 'peers' => []];
        }

        return null;
    }

    /** Defeats and superweapon launches, for markers along the charts. */
    private function gameEvents(array $players, array $superweapons): array
    {
        $houses = array_column($players, 'house');
        $events = [];

        foreach ($players as $p)
        {
            if ($p['defeatedAt'] !== null)
            {
                $events[] = ['t' => $p['defeatedAt'], 'type' => 'defeat', 'house' => $p['house'], 'label' => "{$p['name']} defeated"];
            }
        }

        foreach ($superweapons as [$t, $house, $index])
        {
            if (!in_array($house, $houses, true) || !in_array($index, self::MARKED_SUPERWEAPONS, true))
            {
                continue;
            }
            $events[] = [
                't'     => (int) round($t),
                'type'  => 'superweapon',
                'house' => $house,
                'label' => self::SUPERWEAPONS[$index] ?? 'Superweapon',
            ];
        }

        usort($events, fn ($a, $b) => $a['t'] <=> $b['t']);
        return $events;
    }

    /** Headline facts about the game, each naming the player or team it belongs to. */
    private function highlights(array $stats): array
    {
        $highlights = [];

        // The biggest comeback: the lowest share the eventual winners were down to.
        $momentum = $stats['momentum'];
        if ($momentum !== null)
        {
            foreach ($stats['teams'] as $k => $team)
            {
                if (!$team['won'])
                {
                    continue;
                }

                $shares = $momentum['shares'][$k];
                $lowest = min($shares);
                $at = array_search($lowest, $shares, true);
                if ($lowest < self::COMEBACK_BELOW && $momentum['t'][$at] > self::COMEBACK_AFTER_SECONDS)
                {
                    $highlights[] = [
                        'key'   => 'comeback',
                        'title' => 'Comeback',
                        'house' => $team['members'][0],
                        'value' => (int) round($lowest * 100),
                        't'     => $momentum['t'][$at],
                    ];
                }
            }
        }

        return $highlights;
    }
}
