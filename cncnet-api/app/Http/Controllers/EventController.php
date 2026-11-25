<?php

namespace App\Http\Controllers;

use App\Models\LadderEvent;
use App\Services\LadderEvents\LadderEventService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Vite;

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

        // Local only: ?as_of=2027-01-20 previews the page at another date (statuses, countdowns)
        $previewAt = null;
        if (app()->environment('local') && request()->filled('as_of'))
        {
            try
            {
                $previewAt = Carbon::parse((string) request()->query('as_of'), 'UTC');
            }
            catch (\Throwable $e)
            {
                $previewAt = null;
            }
        }

        $payload = $service->payloadFor($event, $previewAt);

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
            'previewAt' => $previewAt,
            'prizeImage' => $this->prizeImageUrl($slug),
        ]);
    }

    /** Versioned URL of the event's prize graphic, or null if none is configured or it isn't built. */
    private function prizeImageUrl(string $slug): ?string
    {
        $path = config('ladder_events.' . $slug . '.prize_image');
        if (!$path)
        {
            return null;
        }

        try
        {
            return Vite::asset($path) ?: null;
        }
        catch (\Throwable $e)
        {
            // e.g. a local build that predates the image: hide the section rather than fail the page
            return null;
        }
    }
}
