<?php

namespace App\Http\Services;

use App\Models\Achievement;
use App\Models\AchievementProgress;

class AchievementService
{
    public function __construct()
    {
    }

    public function getRecentlyUnlockedAchievements($history, $user, $limit = 5)
    {
        $recentlyUnlocked = AchievementProgress::leftJoin("achievements as a", "achievements_progress.achievement_id", "=", "a.id")
            ->where("user_id", "=", $user->id)
            ->where("a.ladder_id", "=", $history->ladder->id)
            ->where("achievements_progress.achievement_unlocked_date", "!=", null)
            ->orderBy("achievements_progress.achievement_unlocked_date", "=", "DESC")
            ->limit($limit)
            ->get();

        return $recentlyUnlocked;
    }

    public function getProgressCountsByUser($history, $user)
    {
        $total = Achievement::where("ladder_id", $history->ladder->id)->count();
        $unlocked = AchievementProgress::leftJoin("achievements as a", "achievements_progress.achievement_id", "=", "a.id")
            ->where("user_id", "=", $user->id)
            ->where("a.ladder_id", "=", $history->ladder->id)
            ->where("achievements_progress.achievement_unlocked_date", "!=", null)
            ->count();

        if ($total == 0)
        {
            return null;
        }

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
                $groupedByTags[$achievement->tag][$achievement->id]["unlockedProgress"] = $this->getAchievementProgressUntilUnlock($achievement->id, $user->id);
            }
        }

        return $groupedByTags;
    }

    private function getAchievementProgress($achievementId, $userId)
    {
        return AchievementProgress::where("achievement_id", $achievementId)
            ->where("user_id", $userId)
            ->where("achievement_unlocked_date", "!=", null)
            ->first();
    }

    private function getAchievementProgressUntilUnlock($achievementId, $userId)
    {
        $toUnlock = AchievementProgress::leftJoin("achievements as a", "achievements_progress.achievement_id", "=", "a.id")
            ->where("achievements_progress.achievement_id", $achievementId)
            ->where("achievements_progress.user_id", "=", $userId)
            ->select([
                "achievements_progress.count as unlockedCount",
                "a.unlock_count as totalToUnlock"
            ])
            ->first();

        if ($toUnlock)
        {
            return [
                "percentage" => $toUnlock->unlockedCount / $toUnlock->totalToUnlock * 100,
                "totalToUnlock" => $toUnlock->totalToUnlock,
                "unlockedCount" => $toUnlock->unlockedCount,
            ];
        }

        $achievement = Achievement::where("id", $achievementId)->first();

        return [
            "percentage" => 0,
            "totalToUnlock" => $achievement->unlock_count,
            "unlockedCount" => 0
        ];
    }
}
