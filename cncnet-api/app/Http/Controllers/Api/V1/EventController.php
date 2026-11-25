<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LadderEvent;
use App\Services\LadderEvents\LadderEventService;

/**
 * GET /api/v1/events/{slug} — FR-027, FR-029, NFR-001 (reads the precomputed payload only).
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

        return response()->json($service->payloadFor($event));
    }
}
