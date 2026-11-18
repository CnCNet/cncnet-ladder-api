<?php

namespace App\Http\Controllers;

use App\Models\QmMapPick;
use App\Models\QmMap;
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
            // Count vetoes in submission
            $vetoCount = collect($validated['mapChoices'])->where('side_index', -2)->count();

            // Get user's tier and max_vetoes limit
            $userTier = $user->getUserLadderTier($ladder);
            $maxVetoes = $userTier->max_vetoes ?? 0;

            // Enforce veto limit
            if ($vetoCount > $maxVetoes) {
                throw new \InvalidArgumentException(
                    "Too many vetoes: {$vetoCount} submitted, maximum {$maxVetoes} allowed for tier {$userTier->tier}"
                );
            }

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
}
