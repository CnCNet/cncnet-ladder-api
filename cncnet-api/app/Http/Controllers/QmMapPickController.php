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
            'user_id' => 'required|integer|exists:users,id',
            'mapChoices' => 'required|array|max:100',
            'mapChoices.*.qm_map_id' => 'required|integer|exists:qm_maps,id',
            'mapChoices.*.side_index' => 'required|integer|min:-3|max:100',
        ]);

        // Authorization: user can only update their own picks
        if (Auth::id() !== $validated['user_id']) {
            return response()->json([
                'error' => 'Unauthorized to modify other users\' preferences'
            ], 403);
        }

        $ladder = Ladder::findOrFail($validated['ladder_id']);
        $updated = 0;

        try {
            DB::transaction(function () use ($validated, $ladder, &$updated) {
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
                            "Invalid side_index: {$choice['side_index']} (valid: -3, -1, 0-{$maxSide})"
                        );
                    }

                    // Upsert (insert or update)
                    QmMapPick::updateOrCreate(
                        [
                            'user_id' => $validated['user_id'],
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

            return response()->json([
                'success' => true,
                'updated' => $updated,
                'message' => 'Map picks updated successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error updating map picks', [
                'user_id' => $validated['user_id'],
                'ladder_id' => $validated['ladder_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update map picks'
            ], 500);
        }
    }
}
