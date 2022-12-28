<?php

namespace App\Http\Services;

use App\Achievement;
use App\AchievementProgress;
use App\QmLadderRules;

class AchievementService
{
    public function __construct()
    {
    }

    public function getRecentlyUnlockedAchievements($history, $user, $limit = 5)
    {
        return AchievementProgress::leftJoin("achievements as a", "achievements_progress.achievement_id", "=", "a.id")
            ->where("user_id", "=", $user->id)
            ->where("a.ladder_id", "=", $history->ladder->id)
            ->orderBy("achievements_progress.achievement_unlocked_date", "=", "DESC")
            ->limit($limit)
            ->get();
    }

    public function getProgressCountsByUser($history, $user)
    {
        $total = Achievement::where("ladder_id", $history->ladder->id)->count();
        $unlocked = AchievementProgress::leftJoin("achievements as a", "achievements_progress.achievement_id", "=", "a.id")
            ->where("user_id", "=", $user->id)
            ->where("a.ladder_id", "=", $history->ladder->id)
            ->count();

        return [
            "totalToUnlock" => $total,
            "unlockedCount" => $unlocked,
            "percentage" => $unlocked / $total * 100
        ];
    }

    public function groupedByTag($history, $user)
    {
        $groupedByTags = [];
        $achievementTags = $history->ladder->achievements()->pluck("tag")->toArray();
        $achievements = $history->ladder->achievements;

        foreach ($achievements as $achievement)
        {
            if (in_array($achievement->tag, $achievementTags))
            {
                $groupedByTags[$achievement->tag][$achievement->id]["achievement"] = $achievement;
                $groupedByTags[$achievement->tag][$achievement->id]["unlocked"] = $this->getAchievementProgress($achievement->id, $user->id);
            }
        }

        return $groupedByTags;
    }

    private function groupAchievementsByTag($achievements)
    {
    }

    private function getAchievementProgress($achievementId, $userId)
    {
        return AchievementProgress::where("achievement_id", $achievementId)->where("user_id", $userId)->first();
    }
}
