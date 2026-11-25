<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for lookups that were full table scans (found in the slow query log):
 * - game_replay_stats.game_replay_id: ReplayService::enforceStorageBudget nulls it once per evicted
 *   replay, inside the upload request. Without the index each update scanned and locked the whole
 *   table (seconds each), which tied up the app's workers after games ended.
 * - player_game_reports.game_id: Game::player_game_reports and the "has this player reported" check
 * - player_game_reports (player_id, created_at): player profile stats for a date range
 * - games.qm_match_id: finding the game for a QM match
 * - games.created_at: the time window in qm:detect-failed-launches
 * - maps.hash: map lookups by hash
 * - qm_match_states (qm_match_id, state_type_id): exists() checks for a match's state
 *
 * Production already has these (added by hand on 2026-11-23/24). An index is skipped when an existing
 * index, under any name, already starts with the same columns.
 */
return new class extends Migration
{
    private array $indexes = [
        ['game_replay_stats', ['game_replay_id'], 'game_replay_stats_game_replay_id_index'],
        ['player_game_reports', ['game_id'], 'player_game_reports_game_id_index'],
        ['player_game_reports', ['player_id', 'created_at'], 'player_game_reports_player_id_created_at_index'],
        ['games', ['qm_match_id'], 'games_qm_match_id_index'],
        ['games', ['created_at'], 'games_created_at_index'],
        ['maps', ['hash'], 'maps_hash_index'],
        ['qm_match_states', ['qm_match_id', 'state_type_id'], 'qm_match_states_qm_match_id_state_type_id_index'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $columns, $name])
        {
            if ($this->hasIndexOn($table, $columns))
            {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($columns, $name)
            {
                $t->index($columns, $name);
            });
        }
    }

    /**
     * Intentionally empty. On production up() skipped indexes that were already there, so dropping
     * them here would bring back the full table scans this migration is meant to remove.
     */
    public function down(): void
    {
    }

    private function hasIndexOn(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn ($index) => array_slice($index['columns'], 0, count($columns)) === $columns
        );
    }
};
