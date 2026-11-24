<?php

namespace App\Http\Controllers;

use App\Models\LadderEvent;
use App\Services\LadderEvents\LadderEventService;
use Carbon\Carbon;

/**
 * GET /events/{slug} — public event dashboard (FR-026, FR-028, FR-029).
 */
class EventController extends Controller
{
    public function show(string $slug, LadderEventService $service)
    {
        $event = LadderEvent::findBySlug($slug);
        if ($event === null)
        {
            abort(404);
        }

        $payload = $service->payloadFor($event);

        // Group cards by section, in section order (FR-026)
        $sections = [];
        foreach ($payload['sections'] as $section)
        {
            if ($section['key'] === 'overall')
            {
                continue;
            }
            $cards = array_values(array_filter($payload['awards'], fn ($c) => $c['section'] === $section['key']));
            if ($cards)
            {
                $sections[] = $section + ['cards' => $cards];
            }
        }

        return view('events.show', [
            'payload' => $payload,
            'sections' => $sections,
            'starts' => Carbon::parse($payload['event']['starts']),
            'ends' => Carbon::parse($payload['event']['ends']),
            'computedAt' => $payload['computed_at'] ? Carbon::parse($payload['computed_at']) : null,
        ]);
    }
}
