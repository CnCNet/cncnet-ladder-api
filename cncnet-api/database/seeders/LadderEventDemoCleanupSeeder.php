<?php

namespace Database\Seeders;

use App\Models\LadderEvent;
use App\Services\LadderEvents\LadderEventService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * LOCAL ONLY: removes everything LadderEventDemoSeeder created and recomputes the event.
 *
 *   docker exec dev_cncnet_ladder_app php artisan db:seed --class=LadderEventDemoCleanupSeeder
 */
class LadderEventDemoCleanupSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local', 'testing'))
        {
            $this->command?->error('LadderEventDemoCleanupSeeder only runs in the local environment.');
            return;
        }

        $removed = $this->clean();

        $event = LadderEvent::where('slug', '=', LadderEventDemoSeeder::SLUG)->first();
        if ($event !== null)
        {
            // Back to "never computed" so the real dashboard state shows
            $event->computed_payload = null;
            $event->computed_at = null;
            $event->save();
        }

        $this->command?->info("Removed demo data: $removed[users] users, $removed[games] games.");
    }

    /** @return array{users:int, games:int} */
    public function clean(): array
    {
        $userIds = DB::table('users')->where('email', 'like', '%' . LadderEventDemoSeeder::EMAIL_DOMAIN)->pluck('id')->all();
        $playerIds = DB::table('players')->whereIn('user_id', $userIds)->pluck('id')->all();
        $gameIds = DB::table('games')->where('wol_game_id', '=', LadderEventDemoSeeder::DEMO_MARKER)->pluck('id')->all();

        foreach (array_chunk($gameIds, 1000) as $chunk)
        {
            $pgrIds = DB::table('player_game_reports')->whereIn('game_id', $chunk)->pluck('id')->all();
            foreach (array_chunk($pgrIds, 1000) as $pgrChunk)
            {
                DB::table('stats2')->whereIn('player_game_report_id', $pgrChunk)->delete();
            }
            DB::table('player_game_reports')->whereIn('game_id', $chunk)->delete();
            DB::table('game_reports')->whereIn('game_id', $chunk)->delete();
            $qmMatchIds = DB::table('games')->whereIn('id', $chunk)->whereNotNull('qm_match_id')->pluck('qm_match_id')->all();
            DB::table('qm_match_players')->whereIn('qm_match_id', $qmMatchIds)->delete();
            DB::table('qm_matches')->whereIn('id', $qmMatchIds)->delete();
            DB::table('games')->whereIn('id', $chunk)->delete();
        }

        DB::table('player_caches')->whereIn('player_id', $playerIds)->delete();
        DB::table('ladder_event_group_members')->whereIn('user_id', $userIds)->delete();
        DB::table('ladder_event_award_results')
            ->where(fn ($q) => $q->whereIn('user_id', $userIds)->orWhereIn('set_by', $userIds))
            ->delete();
        DB::table('players')->whereIn('id', $playerIds)->delete();
        DB::table('users')->whereIn('id', $userIds)->delete();

        return ['users' => count($userIds), 'games' => count($gameIds)];
    }
}
