<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LadderEvents\SaveEventResultRequest;
use App\Http\Requests\LadderEvents\UpdateEventGroupRequest;
use App\Jobs\ComputeLadderEventJob;
use App\Models\LadderEvent;
use App\Models\LadderEventAwardResult;
use App\Models\LadderEventGroupMember;
use App\Services\LadderEvents\AwardDefinition;
use App\Services\LadderEvents\LadderEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * /admin/events/{slug} — organiser tools (FR-031). Every mutation is activity-logged (FR-032)
 * through the models, or explicitly for finalise / un-finalise.
 */
class EventAdminController extends Controller
{
    const TABS = ['groups', 'results', 'candidates', 'lifecycle'];

    public function __construct(private LadderEventService $service)
    {
    }

    private function event(Request $request): LadderEvent
    {
        return $request->attributes->get('ladderEvent');
    }

    public function show(Request $request, string $slug)
    {
        $event = $this->event($request);
        $def = $event->definition();
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'groups';

        // Groups: members with display name and user key; deleted users flagged
        $rows = DB::table('ladder_event_group_members')
            ->leftJoin('users', 'users.id', '=', 'ladder_event_group_members.user_id')
            ->leftJoin('users as primary_users', 'primary_users.id', '=', 'users.primary_user_id')
            ->where('ladder_event_group_members.ladder_event_id', '=', $event->id)
            ->orderBy('ladder_event_group_members.group_key')
            ->orderBy('ladder_event_group_members.user_id')
            ->get([
                'ladder_event_group_members.group_key', 'ladder_event_group_members.user_id',
                'users.id as found_id', 'users.alias', 'users.name', 'users.primary_user_id',
                'primary_users.alias as primary_alias', 'primary_users.name as primary_name',
            ]);

        $groups = array_fill_keys($def->groups, []);
        foreach ($rows as $r)
        {
            if (!array_key_exists($r->group_key, $groups))
            {
                continue;
            }
            $alias = $r->primary_user_id ? $r->primary_alias : $r->alias;
            $name = $r->primary_user_id ? $r->primary_name : $r->name;
            $groups[$r->group_key][] = [
                'user_id' => (int) $r->user_id,
                'user_key' => $r->found_id === null ? null : (int) ($r->primary_user_id ?? $r->user_id),
                'display' => $r->found_id === null ? null : (trim((string) $alias) !== '' ? $alias : $name),
                'missing' => $r->found_id === null,
            ];
        }

        // User search for the group pickers (by id, alias or name)
        $search = trim((string) $request->query('search', ''));
        $searchResults = [];
        if ($search !== '')
        {
            $q = DB::table('users')->select(['id', 'alias', 'name', 'primary_user_id'])->limit(20);
            if (ctype_digit($search))
            {
                $q->where('id', '=', (int) $search);
            }
            else
            {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('alias', 'like', $like)->orWhere('name', 'like', $like));
            }
            $searchResults = $q->orderBy('id')->get();
        }

        $results = LadderEventAwardResult::where('ladder_event_id', '=', $event->id)
            ->with('user:id,alias,name')
            ->get()
            ->keyBy(fn ($r) => $r->award_key . ':' . $r->kind);

        $candidates = $tab === 'candidates' ? $this->service->candidateWins($event) : [];

        return view('admin.events.show', [
            'event' => $event,
            'def' => $def,
            'tab' => $tab,
            'status' => $this->service->status($event),
            'groups' => $groups,
            'search' => $search,
            'searchGroup' => $request->query('group'),
            'searchResults' => $searchResults,
            'results' => $results,
            'candidates' => $candidates,
        ]);
    }

    public function updateGroups(UpdateEventGroupRequest $request, string $slug)
    {
        $event = $this->event($request);
        $data = $request->validated();

        if ($data['action'] === 'add')
        {
            LadderEventGroupMember::firstOrCreate([
                'ladder_event_id' => $event->id,
                'group_key' => $data['group_key'],
                'user_id' => (int) $data['user_id'],
            ]);
            $message = "Added user #{$data['user_id']} to {$data['group_key']}";
        }
        else
        {
            // Delete through the model so the removal is activity-logged
            LadderEventGroupMember::where('ladder_event_id', '=', $event->id)
                ->where('group_key', '=', $data['group_key'])
                ->where('user_id', '=', (int) $data['user_id'])
                ->get()
                ->each->delete();
            $message = "Removed user #{$data['user_id']} from {$data['group_key']}";
        }

        ComputeLadderEventJob::dispatch($event->id);

        return redirect("/admin/events/{$event->slug}?tab=groups")->with('success', $message);
    }

    public function saveResult(SaveEventResultRequest $request, string $slug)
    {
        $event = $this->event($request);
        $data = $request->validated();
        $kind = $request->kind();

        $result = LadderEventAwardResult::firstOrNew([
            'ladder_event_id' => $event->id,
            'award_key' => $data['award_key'],
            'kind' => $kind,
        ]);
        $result->fill([
            'user_id' => (int) $data['user_id'],
            'game_id' => $data['game_id'] ?? null,
            'note' => $data['note'] ?? null,
            'set_by' => $request->user()->id,
        ]);
        $result->save();

        ComputeLadderEventJob::dispatch($event->id);

        return redirect("/admin/events/{$event->slug}?tab=results")
            ->with('success', ucfirst($kind) . " result saved for {$data['award_key']}");
    }

    public function clearResult(Request $request, string $slug, string $awardKey, string $kind)
    {
        $event = $this->event($request);
        if (!in_array($kind, [LadderEventAwardResult::KIND_MANUAL, LadderEventAwardResult::KIND_OVERRIDE], true))
        {
            abort(404);
        }

        LadderEventAwardResult::where('ladder_event_id', '=', $event->id)
            ->where('award_key', '=', $awardKey)
            ->where('kind', '=', $kind)
            ->get()
            ->each->delete();

        ComputeLadderEventJob::dispatch($event->id);

        return redirect("/admin/events/{$event->slug}?tab=results")->with('success', "Cleared $kind result for $awardKey");
    }

    public function recompute(Request $request, string $slug)
    {
        $event = $this->event($request);
        ComputeLadderEventJob::dispatch($event->id);

        return redirect("/admin/events/{$event->slug}?tab=lifecycle")->with('success', 'Recompute queued');
    }

    public function finalise(Request $request, string $slug)
    {
        $event = $this->event($request);

        if ($this->service->status($event) !== LadderEvent::STATUS_PENDING_FINAL)
        {
            return redirect("/admin/events/{$event->slug}?tab=lifecycle")
                ->withErrors(['finalise' => 'The event can only be finalised after it has ended and before it is final.']);
        }

        $this->service->finalise($event, $request->user());

        return redirect("/admin/events/{$event->slug}?tab=lifecycle")->with('success', 'Event finalised; results are frozen');
    }

    public function unfinalise(Request $request, string $slug)
    {
        $event = $this->event($request);

        if ($event->status_override !== LadderEvent::STATUS_FINAL)
        {
            return redirect("/admin/events/{$event->slug}?tab=lifecycle")
                ->withErrors(['finalise' => 'The event is not finalised.']);
        }

        $this->service->unfinalise($event, $request->user());

        return redirect("/admin/events/{$event->slug}?tab=lifecycle")->with('success', 'Event un-finalised; live results are served again');
    }

    /** Award keys that take a manual result vs an override, for the results form. */
    public static function resultKind(AwardDefinition $award): string
    {
        return $award->type === AwardDefinition::TYPE_MANUAL ? LadderEventAwardResult::KIND_MANUAL : LadderEventAwardResult::KIND_OVERRIDE;
    }
}
