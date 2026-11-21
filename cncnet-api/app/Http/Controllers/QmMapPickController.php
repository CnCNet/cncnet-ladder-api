<?php

namespace App\Http\Controllers;

use App\Models\QmMapPick;
use App\Models\QmMap;
use App\Models\MapTier;
use App\Models\Ladder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QmMapPickController extends Controller
{
    /**
     * Bulk update map picks for a user.
     *
     * POST /api/v1/user/map-picks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ladder_id' => 'required|integer|exists:ladders,id',
            'mapChoices' => 'required|array|max:100',
            'mapChoices.*.qm_map_id' => 'required|integer|exists:qm_maps,id',
            'mapChoices.*.side_index' => 'required|integer|min:-3|max:100',
        ]);

        // User is already authenticated via auth:api middleware
        $user = Auth::user();
        $userId = $user->id;
        $ladder = Ladder::findOrFail($validated['ladder_id']);
        $updated = 0;

        try {
            $this->enforceVetoLimits($validated['mapChoices'], $ladder, $userId);

            DB::transaction(function () use ($userId, $validated, $ladder, &$updated) {
                foreach ($validated['mapChoices'] as $choice) {
                    // Validate map belongs to ladder
                    $map = QmMap::where('id', $choice['qm_map_id'])
                        ->where('ladder_id', $validated['ladder_id'])
                        ->first();

                    if (!$map) {
                        throw new \InvalidArgumentException(
                            "Map {$choice['qm_map_id']} does not belong to ladder {$validated['ladder_id']}"
                        );
                    }

                    // Validate side_index
                    if (!QmMapPick::validateSideIndex($choice['side_index'], $ladder)) {
                        $maxSide = $ladder->sides()->max('local_id');
                        throw new \InvalidArgumentException(
                            "Invalid side_index: {$choice['side_index']} (valid: -3, -2, -1, 0-{$maxSide})"
                        );
                    }

                    // Upsert (insert or update)
                    QmMapPick::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'ladder_id' => $validated['ladder_id'],
                            'qm_map_id' => $choice['qm_map_id'],
                        ],
                        [
                            'side_index' => $choice['side_index'],
                        ]
                    );

                    $updated++;
                }
            });

            Log::info('Map picks updated successfully', [
                'user_id' => $userId,
                'ladder_id' => $validated['ladder_id'],
                'count' => $updated,
            ]);

            return response()->json([
                'success' => true,
                'updated' => $updated,
                'message' => 'Map picks updated successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid map picks data', [
                'user_id' => $userId,
                'ladder_id' => $validated['ladder_id'],
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error updating map picks', [
                'user_id' => $userId,
                'ladder_id' => $validated['ladder_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update map picks'
            ], 500);
        }
    }

    /**
     * Veto limits are defined per map tier (MapTier.max_vetoes, keyed by
     * map_pool_id + tier), not per player tier. Count submitted vetoes for
     * each map tier and compare against that tier's limit.
     *
     * Only valid maps in the ladder's current pool are counted — those are the
     * maps the client displays and counts against the limit. Vetoes on
     * invalid/old-pool maps are harmless since matchmaking never uses them.
     * Maps with no matching MapTier row are not limited.
     *
     * This is a safeguard only; the QM client enforces the limits itself.
     *
     * @param array $mapChoices
     * @param Ladder $ladder
     * @param int $userId
     * @throws \InvalidArgumentException
     */
    private function enforceVetoLimits(array $mapChoices, Ladder $ladder, int $userId): void
    {
        $vetoedMapIds = collect($mapChoices)
            ->where('side_index', -2)
            ->pluck('qm_map_id')
            ->unique();

        if ($vetoedMapIds->isEmpty()) {
            return;
        }

        $vetoesByTier = QmMap::whereIn('id', $vetoedMapIds)
            ->where('map_pool_id', $ladder->map_pool_id)
            ->valid()
            ->get(['id', 'map_pool_id', 'map_tier'])
            ->groupBy(fn($qmMap) => $qmMap->map_pool_id . ':' . $qmMap->map_tier);

        foreach ($vetoesByTier as $maps) {
            $first = $maps->first();
            $mapTier = MapTier::where('map_pool_id', $first->map_pool_id)
                ->where('tier', $first->map_tier)
                ->first();

            if (!$mapTier) {
                continue;
            }

            $vetoCount = $maps->count();
            if ($vetoCount > $mapTier->max_vetoes) {
                Log::warning('Map veto limit exceeded', [
                    'user_id' => $userId,
                    'ladder_id' => $ladder->id,
                    'map_tier' => $mapTier->tier,
                    'max_vetoes' => $mapTier->max_vetoes,
                    'counted_qm_map_ids' => $maps->pluck('id')->values()->all(),
                    'all_submitted_veto_ids' => $vetoedMapIds->values()->all(),
                ]);
                $tierName = $mapTier->name ?: "tier {$mapTier->tier}";
                throw new \InvalidArgumentException(
                    "Too many vetoes: {$vetoCount} submitted, maximum {$mapTier->max_vetoes} allowed for {$tierName} maps"
                );
            }
        }
    }
}
