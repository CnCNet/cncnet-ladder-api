<?php

namespace App\Services\LadderEvents;

use App\Models\LadderEvent;
use App\Models\LadderEventAwardResult;
use App\Models\User;
use App\Services\LadderEvents\Calculators\CountCalculator;
use App\Services\LadderEvents\Calculators\ManualCalculator;
use App\Services\LadderEvents\Calculators\StandingsCalculator;
use App\Services\LadderEvents\Calculators\StreamingCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds, stores and serves the precomputed event payload (NFR-001, NFR-003, NFR-004, FR-030).
 */
class LadderEventService
{
    private CountCalculator $count;
    private StandingsCalculator $standings;
    private StreamingCalculator $streaming;
    private ManualCalculator $manual;

    public function __construct(
        private EventFactsRepository $repo = new EventFactsRepository(),
        private AwardResolver $resolver = new AwardResolver(),
    ) {
        $this->count = new CountCalculator();
        $this->standings = new StandingsCalculator();
        $this->streaming = new StreamingCalculator();
        $this->manual = new ManualCalculator($this->count);
    }

    // ---------------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------------

    /** FR-030: upcoming | live | pending_final | final */
    public function status(LadderEvent $event, ?Carbon $now = null): string
    {
        $now = $now ?? Carbon::now('UTC');
        $def = $event->definition();

        if ($event->status_override === LadderEvent::STATUS_FINAL)
        {
            return LadderEvent::STATUS_FINAL;
        }
        if ($now->lt($def->starts))
        {
            return LadderEvent::STATUS_UPCOMING;
        }
        if ($now->lte($def->ends))
        {
            return LadderEvent::STATUS_LIVE;
        }
        return LadderEvent::STATUS_PENDING_FINAL;
    }

    /**
     * The payload to serve: the frozen snapshot once finalised, else the latest computation.
     * Reads only the model already loaded (no queries). Statuses are refreshed against $now
     * unless frozen; returns a skeleton when nothing has been computed yet.
     */
    public function payloadFor(LadderEvent $event, ?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now('UTC');
        $frozen = $event->status_override === LadderEvent::STATUS_FINAL && $event->frozen_payload !== null;
        $payload = $frozen ? $event->frozen_payload : $event->computed_payload;

        if ($payload === null)
        {
            $payload = $this->skeleton($event);
        }

        $payload['status'] = $this->status($event, $now);

        if (!$frozen)
        {
            foreach ($payload['awards'] as &$card)
            {
                $card['status'] = $this->resolver->windowStatus(Carbon::parse($card['starts'], 'UTC'), Carbon::parse($card['ends'], 'UTC'), $now);
            }
            unset($card);
        }

        return $payload;
    }

    public function finalise(LadderEvent $event, User $by): void
    {
        $payload = $this->compute($event);

        $payload['status'] = LadderEvent::STATUS_FINAL;
        foreach ($payload['awards'] as &$card)
        {
            $card['status'] = LadderEvent::STATUS_FINAL;
        }
        unset($card);

        $event->status_override = LadderEvent::STATUS_FINAL;
        $event->frozen_payload = $payload;
        $event->finalised_at = Carbon::now('UTC');
        $event->finalised_by = $by->id;
        $event->save();

        // FR-032
        activity('admin')->performedOn($event)->causedBy($by)
            ->withProperties(['slug' => $event->slug])
            ->event('finalised')
            ->log("Finalised ladder event {$event->slug}");
    }

    public function unfinalise(LadderEvent $event, User $by): void
    {
        $event->status_override = null;
        $event->frozen_payload = null;
        $event->finalised_at = null;
        $event->finalised_by = null;
        $event->save();

        // FR-032
        activity('admin')->performedOn($event)->causedBy($by)
            ->withProperties(['slug' => $event->slug])
            ->event('unfinalised')
            ->log("Un-finalised ladder event {$event->slug}");
    }

    // ---------------------------------------------------------------------
    // Computation
    // ---------------------------------------------------------------------

    /** Builds the payload, stores it in computed_payload and returns it. */
    public function compute(LadderEvent $event, ?Carbon $now = null): array
    {
        $payload = $this->build($event, $now ?? Carbon::now('UTC'));

        $event->computed_payload = $payload;
        $event->computed_at = Carbon::parse($payload['computed_at'], 'UTC');
        $event->save();

        return $payload;
    }

    public function build(LadderEvent $event, Carbon $now): array
    {
        $def = $event->definition();
        $histories = $event->histories();
        [$ctx, $historiesById] = $this->context($event, $now, $histories);

        $results = LadderEventAwardResult::where('ladder_event_id', '=', $event->id)->get();

        // Raw values per award
        $raw = [];
        foreach ($def->awards as $award)
        {
            $raw[$award->key] = match ($award->type) {
                AwardDefinition::TYPE_STANDINGS => $this->standings->compute($award, $ctx),
                AwardDefinition::TYPE_COUNT => $this->count->compute($award, $ctx),
                AwardDefinition::TYPE_STREAMING => $this->streaming->compute($award, $ctx),
                AwardDefinition::TYPE_MANUAL => $this->manual->compute($award, $ctx),
            };
        }

        // Display names for everyone who can appear (FR-003)
        $keys = [];
        foreach ($raw as $rows)
        {
            foreach ($rows as $r)
            {
                $keys[$r['user_key']] = true;
            }
        }
        foreach ($ctx->groups as $members)
        {
            $keys += $members;
        }
        $resultKeys = [];
        foreach ($results as $res)
        {
            $resultKeys[$res->id] = $this->repo->userKeyFor((int) $res->user_id) ?? (int) $res->user_id;
            $keys[$resultKeys[$res->id]] = true;
        }
        $names = $this->repo->userDisplay(array_keys($keys));
        $urls = $this->profileUrls($ctx->facts, $historiesById, $def->ladder);
        $gameUrls = $this->gameUrls($results->pluck('game_id')->filter()->all(), $def->ladder);

        // Rank, then cross-award exclusivity (FR-018)
        $ranked = [];
        $awardsByKey = [];
        foreach ($def->cardAwards() as $award)
        {
            $awardsByKey[$award->key] = $award;
            $ranked[$award->key] = $award->type === AwardDefinition::TYPE_MANUAL ? [] : $this->resolver->rank($raw[$award->key], $names);
        }
        [$ranked, $notes] = $this->resolver->applyExclusivity($ranked, $awardsByKey);

        $resultFor = function (string $awardKey, string $kind) use ($results, $resultKeys, $names, $gameUrls) {
            $res = $results->first(fn ($r) => $r->award_key === $awardKey && $r->kind === $kind);
            if ($res === null)
            {
                return null;
            }
            $key = $resultKeys[$res->id];
            return [
                'kind' => $kind,
                'user_key' => $key,
                'name' => $names[$key] ?? ('#' . $key),
                'game_id' => $res->game_id === null ? null : (int) $res->game_id,
                'game_url' => $res->game_id === null ? null : ($gameUrls[(int) $res->game_id] ?? null),
                'note' => $res->note,
            ];
        };

        // Cards
        $cards = [];
        foreach ($def->cardAwards() as $award)
        {
            [$start, $end] = $def->awardWindow($award);
            $isManual = $award->type === AwardDefinition::TYPE_MANUAL;

            $extra = [];
            if ($isManual)
            {
                $extra['candidate_count'] = $award->candidates === null ? null : (int) array_sum(array_column($raw[$award->key], 'value'));
                $extra['selector'] = $award->selectorLabel;
                $extra['decided_by'] = $award->decidedBy;
            }

            $card = $this->resolver->card(
                $award,
                $this->description($award, $ctx, $names),
                $ranked[$award->key],
                $notes[$award->key] ?? [],
                $resultFor($award->key, $isManual ? LadderEventAwardResult::KIND_MANUAL : LadderEventAwardResult::KIND_OVERRIDE),
                $start,
                $end,
                $now,
                $extra
            );
            $card['leaders'] = $this->withUrls($card['leaders'], $urls);
            $card['entries'] = $this->withUrls($card['entries'], $urls);
            $cards[] = $card;
        }

        // Standings
        $months = [];
        foreach ($histories as $h)
        {
            $months[] = ['key' => $this->monthKey($h), 'label' => $h->starts->format('M Y'), 'history_id' => (int) $h->id];
        }
        $standings = [];
        $standingsAward = $def->standingsAward();
        if ($standingsAward !== null)
        {
            $rankedStandings = $this->resolver->rank($raw[$standingsAward->key], $names);
            foreach ($this->resolver->withPrizes($rankedStandings, $standingsAward->prizes, AwardResolver::STANDINGS_LIMIT) as $e)
            {
                $row = [
                    'place' => $e['place'],
                    'tied' => $e['tied'],
                    'user_key' => $e['user_key'],
                    'name' => $e['name'],
                    'points' => $e['value'],
                ];
                foreach ($months as $m)
                {
                    $row[$m['key']] = $e['months'][$m['history_id']] ?? 0;
                }
                $row['prize'] = $e['prize'];
                $row['url'] = $urls[$e['user_key']] ?? null;
                $standings[] = $row;
            }
        }

        return [
            'event' => $this->eventBlock($event),
            'status' => $this->status($event, $now),
            'computed_at' => AwardResolver::iso($now),
            'months' => array_map(fn ($m) => ['key' => $m['key'], 'label' => $m['label']], $months),
            'sections' => $this->sections($def),
            'standings' => $standings,
            'awards' => $cards,
        ];
    }

    /**
     * @return array{0: EventContext, 1: array<int, \App\Models\LadderHistory>}
     */
    public function context(LadderEvent $event, Carbon $now, ?Collection $histories = null): array
    {
        $def = $event->definition();
        $histories = $histories ?? $event->histories();
        $historyIds = $histories->pluck('id')->map(fn ($id) => (int) $id)->all();

        $ctx = new EventContext(
            definition: $def,
            facts: $this->repo->gameFacts($historyIds)->all(),
            streams: $this->repo->streamFacts((int) $event->ladder_id, $historyIds)->all(),
            groups: $this->repo->groups($event, $def->groups),
            standings: $this->repo->monthlyStandings($historyIds),
            historyIds: $historyIds,
            now: $now,
        );

        return [$ctx, $histories->keyBy('id')->all()];
    }

    // ---------------------------------------------------------------------
    // Admin helpers
    // ---------------------------------------------------------------------

    /**
     * FR-021: candidate wins for a "selected by X" award, newest first, with game details.
     * @return list<array{game_id:int, played_at:string, user_key:int, name:string, partner:string, opponents:string[], map:?string, url:string}>
     */
    public function candidateWins(LadderEvent $event, AwardDefinition $award): array
    {
        if ($award->candidates === null)
        {
            return [];
        }

        [$ctx, $historiesById] = $this->context($event, Carbon::now('UTC'));
        $facts = $this->manual->candidateFacts($award, $ctx);

        $keys = [];
        foreach ($facts as $f)
        {
            $keys[$f->userKey] = true;
            $keys[$f->partnerKey] = true;
            foreach ($f->opponentKeys as $k)
            {
                $keys[$k] = true;
            }
        }
        $names = $this->repo->userDisplay(array_keys($keys));
        $nameOf = fn ($k) => $names[$k] ?? ('#' . $k);

        $gameIds = array_values(array_unique(array_map(fn ($f) => $f->gameId, $facts)));
        $maps = [];
        foreach (array_chunk($gameIds, 1000) as $chunk)
        {
            $rows = DB::table('games')
                ->leftJoin('qm_maps', 'qm_maps.id', '=', 'games.qm_map_id')
                ->whereIn('games.id', $chunk)
                ->get(['games.id', 'games.scen', 'qm_maps.description']);
            foreach ($rows as $r)
            {
                $maps[(int) $r->id] = $r->description ?: $r->scen;
            }
        }

        $def = $event->definition();
        return array_map(fn (GameFact $f) => [
            'game_id' => $f->gameId,
            'played_at' => gmdate('Y-m-d H:i', $f->playedAt) . ' UTC',
            'user_key' => $f->userKey,
            'name' => $nameOf($f->userKey),
            'partner' => $nameOf($f->partnerKey),
            'opponents' => array_map($nameOf, $f->opponentKeys),
            'map' => $maps[$f->gameId] ?? null,
            'url' => '/ladder/' . $historiesById[$f->historyId]->short . '/' . $def->ladder . '/games/' . $f->gameId,
        ], $facts);
    }

    /**
     * FR-022: validate a manual result / override game. Returns an error message, or null when valid.
     */
    public function validateResultGame(LadderEvent $event, AwardDefinition $award, int $userId, int $gameId): ?string
    {
        $historyIds = $event->histories()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $facts = $this->repo->gameFacts($historyIds, $gameId);

        if ($facts->isEmpty())
        {
            return 'Game is not part of this event';
        }

        $userKey = $this->repo->userKeyFor($userId);
        $mine = $facts->first(fn (GameFact $f) => $f->userKey === $userKey);
        if ($mine === null)
        {
            return 'The winner did not play in this game';
        }

        if ($award->candidates !== null)
        {
            $group = $this->repo->groups($event, [$award->candidates])[$award->candidates];
            if (!$mine->win || $mine->partnerKey === null || !isset($group[$mine->partnerKey]))
            {
                return 'Game is not one of the candidate wins for this award';
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    private function skeleton(LadderEvent $event): array
    {
        $def = $event->definition();
        return [
            'event' => $this->eventBlock($event),
            'status' => null,
            'computed_at' => null,
            'months' => [],
            'sections' => $this->sections($def),
            'standings' => [],
            'awards' => [],
        ];
    }

    private function eventBlock(LadderEvent $event): array
    {
        $def = $event->definition();
        return [
            'slug' => $def->slug,
            'name' => $def->name,
            'ladder' => $def->ladder,
            'starts' => AwardResolver::iso($def->starts),
            'ends' => AwardResolver::iso($def->ends),
        ];
    }

    private function sections(LadderEventDefinition $def): array
    {
        $out = [];
        foreach ($def->sections as $key => $s)
        {
            $out[] = ['key' => $key, 'title' => $s['title'] ?? $key, 'emoji' => $s['emoji'] ?? ''];
        }
        return $out;
    }

    private function monthKey($history): string
    {
        return strtolower($history->starts->format('M'));
    }

    /** ":members" → "A, B and C" from the award's group (rendered from the group, not hardcoded). */
    private function description(AwardDefinition $award, EventContext $ctx, array $names): string
    {
        if (!str_contains($award->description, ':members'))
        {
            return $award->description;
        }

        $members = array_map(fn ($k) => $names[$k] ?? ('#' . $k), array_keys($ctx->group((string) $award->descriptionGroup())));
        sort($members, SORT_FLAG_CASE | SORT_STRING);

        $list = match (count($members)) {
            0 => 'the named players',
            1 => $members[0],
            default => implode(', ', array_slice($members, 0, -1)) . ' and ' . end($members),
        };

        return str_replace(':members', $list, $award->description);
    }

    /**
     * FR-026: link each user to the player page of the most recent history they played,
     * using a player owned by the primary account itself (alt players are not exposed, NFR-006).
     *
     * @param GameFact[] $facts
     * @return array<int, string>
     */
    private function profileUrls(array $facts, array $historiesById, string $ladderAbbrev): array
    {
        $latest = [];
        foreach ($facts as $f)
        {
            if ($f->ownerUserId !== $f->userKey)
            {
                continue;
            }
            $cur = $latest[$f->userKey] ?? null;
            if ($cur === null || [$f->playedAt, $f->gameId] > [$cur->playedAt, $cur->gameId])
            {
                $latest[$f->userKey] = $f;
            }
        }

        $out = [];
        foreach ($latest as $key => $f)
        {
            $short = $historiesById[$f->historyId]->short;
            $out[$key] = '/ladder/' . $short . '/' . $ladderAbbrev . '/player/' . rawurlencode($f->username);
        }
        return $out;
    }

    /** @return array<int, string> game id => game page url */
    private function gameUrls(array $gameIds, string $ladderAbbrev): array
    {
        if (count($gameIds) === 0)
        {
            return [];
        }

        $rows = DB::table('games')
            ->join('ladder_history', 'ladder_history.id', '=', 'games.ladder_history_id')
            ->whereIn('games.id', array_values(array_unique($gameIds)))
            ->get(['games.id', 'ladder_history.short']);

        $out = [];
        foreach ($rows as $r)
        {
            $out[(int) $r->id] = '/ladder/' . $r->short . '/' . $ladderAbbrev . '/games/' . $r->id;
        }
        return $out;
    }

    private function withUrls(array $entries, array $urls): array
    {
        foreach ($entries as &$e)
        {
            $e['url'] = $urls[$e['user_key']] ?? null;
        }
        unset($e);
        return $entries;
    }
}
