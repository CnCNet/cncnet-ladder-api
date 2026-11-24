<?php

namespace App\Jobs;

use App\Models\LadderEvent;
use App\Services\LadderEvents\LadderEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * "Recompute now" from the event admin page, so the web request does not block (FR-031).
 */
class ComputeLadderEventJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 120;
    public int $tries = 1;

    public function __construct(public int $ladderEventId)
    {
    }

    public function uniqueId(): string
    {
        return 'ladder-event-' . $this->ladderEventId;
    }

    public function handle(LadderEventService $service): void
    {
        $event = LadderEvent::find($this->ladderEventId);
        if ($event === null)
        {
            return;
        }

        try
        {
            $service->compute($event);
        }
        catch (\Throwable $e)
        {
            Log::error('ComputeLadderEventJob failed', ['slug' => $event->slug, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
