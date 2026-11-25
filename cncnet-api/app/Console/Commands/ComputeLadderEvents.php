<?php

namespace App\Console\Commands;

use App\Models\LadderEvent;
use App\Services\LadderEvents\LadderEventDefinition;
use App\Services\LadderEvents\LadderEventService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the precomputed ladder event payload(s). NFR-002, NFR-003.
 *
 *   php artisan events:compute                       # all live / pending_final events
 *   php artisan events:compute ra2-2v2-holiday-2026  # one event, whatever its status
 */
class ComputeLadderEvents extends Command
{
    protected $signature = 'events:compute {slug? : Event slug; omit to compute every live or pending_final event}';

    protected $description = 'Compute ladder event dashboards (awards, standings) into ladder_events.computed_payload';

    public function handle(LadderEventService $service): int
    {
        $slug = $this->argument('slug');

        // Fail fast on invalid config (design: Error Handling)
        foreach (array_keys(config('ladder_events', [])) as $key)
        {
            if ($slug === null || $slug === $key)
            {
                LadderEventDefinition::fromConfig($key, config('ladder_events.' . $key));
            }
        }

        if ($slug !== null)
        {
            $event = LadderEvent::findBySlug($slug);
            if ($event === null)
            {
                $this->error("Unknown ladder event '$slug'");
                return self::FAILURE;
            }
            $events = collect([$event]);
        }
        else
        {
            $events = LadderEvent::whereIn('slug', array_keys(config('ladder_events', [])))->get()
                ->filter(fn ($e) => in_array($service->status($e), [LadderEvent::STATUS_LIVE, LadderEvent::STATUS_PENDING_FINAL], true));
        }

        $failed = false;
        foreach ($events as $event)
        {
            $started = microtime(true);
            try
            {
                $service->compute($event);
                $this->info(sprintf(
                    'Computed %s in %.2fs (peak memory %.1f MB)',
                    $event->slug,
                    microtime(true) - $started,
                    memory_get_peak_usage(true) / 1048576
                ));
            }
            catch (\Throwable $e)
            {
                // Previous computed_payload is kept.
                $failed = true;
                Log::error('events:compute failed', ['slug' => $event->slug, 'error' => $e->getMessage()]);
                $this->error("Failed to compute {$event->slug}: " . $e->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
