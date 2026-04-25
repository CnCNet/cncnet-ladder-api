<?php

namespace App\Http\Services;

use App\Models\QmMatch;
use Illuminate\Support\Collection;

class ConnectionStatsService
{
    /**
     * Attach ping information to player game reports
     *
     * This method calculates and adds a 'pings' attribute to each PlayerGameReport
     * based on the QM connection statistics.
     *
     * @param Collection $playerGameReports Collection of PlayerGameReport models
     * @param QmMatch|null $qmMatch The quick match data (may be null for non-QM games)
     * @return void
     */
    public function attachPingsToPlayerReports(
        Collection $playerGameReports,
        ?QmMatch $qmMatch
    ): void {
        foreach ($playerGameReports as $pgr) {
            $pgr->pings = $this->calculatePingsForPlayer($pgr, $qmMatch);
        }
    }

    /**
     * Calculate ping string for a specific player
     *
     * @param mixed $pgr PlayerGameReport
     * @param QmMatch|null $qmMatch
     * @return string Comma-separated ping values or '?' if unavailable
     */
    private function calculatePingsForPlayer($pgr, ?QmMatch $qmMatch): string
    {
        // If no QM match, return unknown
        if ($qmMatch === null) {
            return '?';
        }

        // Get connection stats for this player
        $connectionStats = $qmMatch->qmConnectionStats
            ->where('player_id', $pgr->player_id);

        // If no connection stats, return unknown
        if ($connectionStats->isEmpty()) {
            return '?';
        }

        // Extract RTT values
        $pingsArr = $connectionStats->map(function ($connectionStat) {
            return $connectionStat?->rtt ?? -1;
        })->all();

        // Return comma-separated pings or '?' if none
        return !empty($pingsArr) ? implode(', ', $pingsArr) : '?';
    }
}
