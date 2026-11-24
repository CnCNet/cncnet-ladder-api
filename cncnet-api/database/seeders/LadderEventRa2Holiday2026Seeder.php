<?php

namespace Database\Seeders;

use App\Models\Ladder;
use App\Models\LadderEvent;
use App\Models\LadderEventGroupMember;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the RA2 2v2 Holiday Championship event and its named groups.
 *
 * The user ids below are local candidates (local DB current to Aug 2026).
 * They MUST be verified against production before 2026-12-01; organisers can
 * also correct them on /admin/events/ra2-2v2-holiday-2026.
 *
 * php artisan db:seed --class=LadderEventRa2Holiday2026Seeder
 */
class LadderEventRa2Holiday2026Seeder extends Seeder
{
    const SLUG = 'ra2-2v2-holiday-2026';

    const GROUPS = [
        's_class' => [3118, 17651, 41776, 35501, 928],   // Latof, Qien, Ossama, Lgnd, Marko
        'partner_mikoz' => [19693],                        // Mikoz
        'partner_zedd_beta' => [13, 51953],                // Zedd, Beta
        'partner_niek_dayi' => [14579, 71425],             // Niek, Dayi
        'selector_gino' => [5299],                         // Gino
        'selector_edd' => [2152],                          // Edd
        'selector_steve' => [18323],                       // Monster (shown as "Steve")
        'selector_sander' => [5097],                       // Sander
        'selector_andy' => [6026],                         // Andy
    ];

    public function run(): void
    {
        $config = config('ladder_events.' . self::SLUG);
        $ladder = Ladder::where('abbreviation', '=', $config['ladder'])->first();

        if ($ladder === null)
        {
            $this->command?->error("Ladder '{$config['ladder']}' not found; event not seeded.");
            return;
        }

        $event = LadderEvent::firstOrCreate(
            ['slug' => self::SLUG],
            ['ladder_id' => $ladder->id]
        );

        foreach (self::GROUPS as $group => $userIds)
        {
            foreach ($userIds as $userId)
            {
                if (!User::where('id', '=', $userId)->exists())
                {
                    $this->command?->warn("User #$userId ($group) not found; skipped.");
                    continue;
                }

                LadderEventGroupMember::firstOrCreate([
                    'ladder_event_id' => $event->id,
                    'group_key' => $group,
                    'user_id' => $userId,
                ]);
            }
        }

        $this->command?->info("Seeded ladder event " . self::SLUG . " (id {$event->id}).");
    }
}
