<?php

namespace Tests\Feature\LadderEvents;

use App\Models\GameObjectSchema;
use App\Models\Ladder;
use App\Models\LadderEvent;
use App\Models\LadderEventGroupMember;
use App\Models\LadderHistory;
use App\Models\Player;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds a minimal RA2 2v2 holiday event on top of testing-schema.sql.
 */
trait EventFixtures
{
    const SLUG = 'ra2-2v2-holiday-2026';

    protected Ladder $ladder;
    protected LadderHistory $dec;
    protected LadderHistory $jan;
    protected LadderHistory $nov;
    protected LadderEvent $event;

    /** @var array<int, Player> user id => latest player made by makeUser */
    protected array $players = [];

    protected function setUpEvent(): void
    {
        $this->ladder = Ladder::factory()->create([
            'abbreviation' => 'ra2-2v2',
            'game' => 'yr',
            'game_object_schema_id' => GameObjectSchema::factory(),
        ]);
        $this->ladder->ladder_type = Ladder::TWO_VS_TWO;
        $this->ladder->save();

        $this->nov = $this->makeHistory('11-2026', '2026-11-01', '2026-11-30 23:59:59');
        $this->dec = $this->makeHistory('12-2026', '2026-12-01', '2026-12-31 23:59:59');
        $this->jan = $this->makeHistory('1-2027', '2027-01-01', '2027-01-31 23:59:59');

        $this->event = LadderEvent::create(['slug' => self::SLUG, 'ladder_id' => $this->ladder->id]);
    }

    protected function makeHistory(string $short, string $starts, string $ends): LadderHistory
    {
        return LadderHistory::create([
            'ladder_id' => $this->ladder->id,
            'short' => $short,
            'starts' => Carbon::parse($starts, 'UTC'),
            'ends' => Carbon::parse($ends, 'UTC'),
        ]);
    }

    /**
     * Create a user and one player for them. Options: alias, primary (users.id), is_bot, group (string)
     */
    protected function makeUser(string $name, array $opts = []): User
    {
        $user = new User();
        $user->name = $name;
        $user->email = strtolower($name) . '-' . uniqid() . '@test.com';
        $user->password = 'x';
        $user->alias = $opts['alias'] ?? $name;
        $user->save();

        if (isset($opts['primary']))
        {
            DB::table('users')->where('id', $user->id)->update(['primary_user_id' => $opts['primary']]);
        }

        $this->makePlayer($user, $opts['player'] ?? $name, $opts['is_bot'] ?? false);

        if (isset($opts['group']))
        {
            $this->addToGroup($opts['group'], $user);
        }

        return $user;
    }

    protected function makePlayer(User $user, string $username, bool $isBot = false): Player
    {
        $player = new Player();
        $player->user_id = $user->id;
        $player->username = $username;
        $player->ladder_id = $this->ladder->id;
        $player->card_id = 0;
        $player->save();

        if ($isBot)
        {
            DB::table('players')->where('id', $player->id)->update(['is_bot' => true]);
        }

        $this->players[$user->id] = $player;
        return $player;
    }

    protected function addToGroup(string $group, User $user): void
    {
        LadderEventGroupMember::create([
            'ladder_event_id' => $this->event->id,
            'group_key' => $group,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Record one 2v2 game. $teamA wins unless opts['draw'].
     * Each side entry is a User, or [User, country] to set stats2.cty.
     * Options: at (UTC string), history, valid, best_report, draw, duration, spectators (User[]),
     *          team_a_label / team_b_label (null for a NULL team), qm (array of user id => ['live'=>bool,'observer'=>bool])
     */
    protected function addGame(array $teamA, array $teamB, array $opts = []): int
    {
        $at = Carbon::parse($opts['at'] ?? '2026-12-10 12:00:00', 'UTC');
        $history = $opts['history'] ?? ($at->month === 1 ? $this->jan : ($at->month === 11 ? $this->nov : $this->dec));
        $draw = $opts['draw'] ?? false;

        $qmMatchId = null;
        if (isset($opts['qm']))
        {
            $qmMatchId = DB::table('qm_matches')->insertGetId([
                'ladder_id' => $this->ladder->id, 'qm_map_id' => 0, 'seed' => 1, 'tier' => 1,
                'created_at' => $at, 'updated_at' => $at,
            ]);
        }

        $gameId = DB::table('games')->insertGetId([
            'ladder_history_id' => $history->id,
            'wol_game_id' => 0,
            'crat' => 0, 'cred' => '', 'shrt' => 0, 'supr' => 0, 'unit' => 0, 'plrs' => 4,
            'qm_match_id' => $qmMatchId,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        $first = is_array($teamA[0]) ? $teamA[0][0] : $teamA[0];
        $reportId = DB::table('game_reports')->insertGetId([
            'game_id' => $gameId,
            'player_id' => $this->players[$first->id]->id,
            'best_report' => $opts['best_report'] ?? true,
            'manual_report' => false,
            'duration' => $opts['duration'] ?? 600,
            'valid' => $opts['valid'] ?? true,
            'finished' => true,
            'fps' => 60,
            'oos' => false,
            'clan_id' => 0,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        $sides = [
            [$teamA, array_key_exists('team_a_label', $opts) ? $opts['team_a_label'] : 'A', !$draw],
            [$teamB, array_key_exists('team_b_label', $opts) ? $opts['team_b_label'] : 'B', false],
        ];
        foreach ($sides as [$members, $label, $won])
        {
            foreach ($members as $entry)
            {
                [$user, $country] = is_array($entry) ? $entry : [$entry, null];
                $this->addReport($gameId, $reportId, $user, $label, $won ? 20 : ($draw ? 0 : -10), $draw, false, $country, $at);
            }
        }
        foreach ($opts['spectators'] ?? [] as $spec)
        {
            $this->addReport($gameId, $reportId, $spec, null, 0, false, true, null, $at);
        }

        foreach ($opts['qm'] ?? [] as $userId => $q)
        {
            DB::table('qm_match_players')->insert([
                'waiting' => false,
                'player_id' => $this->players[$userId]->id,
                'ladder_id' => $this->ladder->id,
                'map_bitfield' => 0, 'chosen_side' => 0, 'actual_side' => 0, 'tier' => 1,
                'qm_match_id' => $qmMatchId,
                'is_observer' => $q['observer'] ?? false,
                'twitch_live_at_start' => $q['live'] ?? false,
                'created_at' => $at, 'updated_at' => $at,
            ]);
        }

        return $gameId;
    }

    private function addReport(int $gameId, int $reportId, User $user, ?string $team, int $points, bool $draw, bool $spectator, ?int $country, Carbon $at): void
    {
        $pgrId = DB::table('player_game_reports')->insertGetId([
            'game_id' => $gameId,
            'game_report_id' => $reportId,
            'player_id' => $this->players[$user->id]->id,
            'local_id' => 0,
            'local_team_id' => 0,
            'team' => $team,
            'points' => $points,
            'won' => $points > 0,
            'draw' => $draw,
            'spectator' => $spectator,
            'clan_id' => 0,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        if ($country !== null)
        {
            $statsId = DB::table('stats2')->insertGetId([
                'cty' => $country,
                'player_game_report_id' => $pgrId,
            ]);
            DB::table('player_game_reports')->where('id', $pgrId)->update(['stats_id' => $statsId]);
        }
    }

    protected function setPoints(User $user, LadderHistory $history, int $points, ?Player $player = null): void
    {
        $player = $player ?? $this->players[$user->id];
        DB::table('player_caches')->insert([
            'ladder_history_id' => $history->id,
            'player_id' => $player->id,
            'player_name' => $player->username,
            'points' => $points,
            'wins' => 0, 'games' => 0, 'percentile' => 0, 'fps' => 0,
        ]);
    }

    /** Make $n filler users ranked above everyone else in $history with descending points. */
    protected function fillRanks(LadderHistory $history, int $n, int $topPoints = 10000): void
    {
        for ($i = 0; $i < $n; $i++)
        {
            $u = $this->makeUser('Filler' . $history->short . '_' . $i);
            $this->setPoints($u, $history, $topPoints - $i);
        }
    }
}
