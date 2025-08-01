<?php

namespace App\Http\Controllers;

use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\PlayerCache;
use App\Models\IpAddressHistory;
use App\Models\QmUserId;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ActiveDuplicatesController extends Controller
{
    public function index(Request $request)
    {
        $ladderAbbreviation = $request->abbreviation ?? \App\Helpers\GameHelper::$GAME_RA2;
        $ladder = Ladder::where('abbreviation', $ladderAbbreviation)->first();
        $period = $request->get('period', 'current');
        $targetMonth = $period === 'last' ? now()->subMonthNoOverflow() : now();
        $startDate = $targetMonth->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();
        $activeLadders = $period === 'last' ? Ladder::whereHas('last_month_matches')->get() : Ladder::whereHas('current_month_matches')->get();

        $currentHistory = LadderHistory::where('ladder_id', $ladder->id)
            ->where('starts', '=', $startDate->toDateTimeString())
            ->where('ends', '=', $endDate->toDateTimeString())
            ->first();

        $playerCaches = PlayerCache::where('ladder_history_id', $currentHistory->id)
            ->with('player.user')
            ->get();

        $potentialDuplicates = [];

        foreach ($playerCaches as $cache)
        {
            $user = $cache->player->user;

            if (!$user || !$user->isUnconfirmedPrimary())
            {
                continue;
            }

            $relatedUsers = $this->findPotentialDuplicatesDetailed($user);

            if ($relatedUsers->isNotEmpty())
            {
                $potentialDuplicates[] = [
                    'user' => $user,
                    'player' => $cache->player,
                    'dupes' => $relatedUsers,
                ];
            }
        }

        return view('admin.active-duplicates', [
            'potentialDuplicates' => $potentialDuplicates,
            'ladder' => $ladder,
            'period' => $period,
            'activeLadders' => $activeLadders,
            'selectedAbbreviation' => $ladder->abbreviation,
        ]);
    }

    private function findPotentialDuplicatesDetailed(User $user): Collection
    {
        $dupesMap = [];

        // IP-based duplicates.
        if ($user->ip_address_id)
        {
            $ipDupes = IpAddressHistory::with('user')
                ->where('ip_address_id', $user->ip_address_id)
                ->where('user_id', '!=', $user->id)
                ->get()
                ->pluck('user')
                ->filter()
                ->unique('id');

            foreach ($ipDupes as $dupe)
            {
                $id = $dupe->id;
                if (!isset($dupesMap[$id]))
                {
                    $dupesMap[$id] = [
                        'user' => $dupe,
                        'reasons' => [],
                    ];
                }
                $dupesMap[$id]['reasons'][] = 'IP';
            }
        }

        // QM-ID-based duplicates.
        $qmIds = QmUserId::where('user_id', $user->id)->pluck('qm_user_id')->unique();

        if ($qmIds->isNotEmpty())
        {
            $qmDupes = QmUserId::with('user')
                ->whereIn('qm_user_id', $qmIds)
                ->where('user_id', '!=', $user->id)
                ->get()
                ->pluck('user')
                ->filter()
                ->unique('id');

            foreach ($qmDupes as $dupe)
            {
                $id = $dupe->id;
                if (!isset($dupesMap[$id]))
                {
                    $dupesMap[$id] = [
                        'user' => $dupe,
                        'reasons' => [],
                    ];
                }
                $dupesMap[$id]['reasons'][] = 'QM-ID';
            }
        }

        return collect($dupesMap)->values();
    }

}