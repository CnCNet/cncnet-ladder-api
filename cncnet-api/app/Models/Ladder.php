<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ladder extends Model
{
    use HasFactory;
    protected $table = 'ladders';

    const ONE_VS_ONE = '1vs1';
    const TWO_VS_TWO = '2vs2';
    const CLAN_MATCH = 'clan_match';

    const LADDER_TYPES = [
        Ladder::ONE_VS_ONE, Ladder::TWO_VS_TWO, Ladder::CLAN_MATCH
    ];

    protected $fillable = [
        'name',
        'abbreviation',
        'game',
        'clans_allowed',
        'game_object_schema_id',
        'private',
        'map_pool_id',
    ];

    public function allowedToView($user)
    {
        if ($this->private == false)
            return true;

        if ($user === null)
            return false;

        if ($user->isGod())
            return true;

        return $user->isLadderAdmin($this) || $user->isLadderMod($this) || $user->isLadderTester($this);
    }

    public function currentHistory() : ?LadderHistory
    {
        $start = now()->startOfMonth()->toDateTimeString();
        $end = now()->endOfMonth()->toDateTimeString();

        return LadderHistory::where('ladder_id', '=', $this->id)
            ->where('ladder_history.starts', '=', $start)
            ->where('ladder_history.ends', '=', $end)->first();
    }

    public function latestLeaderboardUrl()
    {
        $history = $this->currentHistory();
        if ($history === null)
        {
            return "/";
        }

        $ladder = $history->ladder;

        return "/ladder/{$history->short}/$ladder->abbreviation";
    }

    /**
     * Returns array of QM ladders user has access to
     * Show private ladders to ladder testers only
     * @param User $user 
     * @return array 
     */
    public static function getAllowedQMLaddersByUser(User $user, bool $onlyReturnPrivateLadders = false)
    {
        $userAllowedLadders = [];

        $ladders = Ladder::all();
        if ($onlyReturnPrivateLadders === true)
        {
            $ladders = Ladder::where("private", true)->get();
        }

        foreach ($ladders as $ladder)
        {
            // Show private ladders to ladder testers only
            if ($ladder->private == true)
            {
                if (!$user->isLadderAdmin($ladder) || !$user->isLadderTester($ladder))
                {
                    continue;
                }
            }

            $userAllowedLadders[] = $ladder;
        }

        return $userAllowedLadders;
    }

    # Relationships
    public function qmLadderRules()
    {
        return $this->hasOne(QmLadderRules::class);
    }

    public function sides()
    {
        return $this->hasMany(Side::class);
    }

    public function qmMaps()
    {
        return $this->hasMany(QmMap::class);
    }

    public function current_history() {
        $start = now()->startOfMonth()->toDateTimeString();
        $end = now()->endOfMonth()->toDateTimeString();
        return $this->hasOne(LadderHistory::class)
            ->where('ladder_history.starts', '=', $start)
            ->where('ladder_history.ends', '=', $end);

    }

    public function maps()
    {
        return $this->hasMany(Map::class);
    }

    public function mapPools()
    {
        return $this->hasMany(MapPool::class);
    }

    public function mapPool()
    {
        return $this->belongsTo(MapPool::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function allAdmins()
    {
        return $this->hasMany(LadderAdmin::class);
    }

    public function admins()
    {
        return $this->allAdmins()->where('admin', '=', true);
    }

    public function moderators()
    {
        return $this->allAdmins()->where('moderator', '=', true);
    }

    public function testers()
    {
        return $this->allAdmins()->where('tester', '=', true);
    }

    public function alerts()
    {
        return $this->hasMany(LadderAlert::class)->where('expires_at', '>', Carbon::now());
    }

    public function gameObjectSchema()
    {
        return $this->belongsTo(GameObjectSchema::class);
    }

    public function countableGameObjects()
    {
        return $this->hasMany(CountableGameObject::class, 'game_object_schema_id', 'game_object_schema_id');
    }

    public function spawnOptionValues()
    {
        return $this->hasMany(SpawnOptionValue::class);
    }

    public function qmCanceledMatches()
    {
        return $this->hasMany(QmCanceledMatch::class, 'ladder_id');
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class, 'ladder_id');
    }

    public function recent_matched_players()
    {
        return $this->hasMany(QmMatchPlayer::class)
            ->where('qm_match_players.created_at', '>', Carbon::now()->subHour());
    }

    public function recent_matches()
    {
        return $this->hasMany(QmMatch::class)
            ->where('qm_matches.created_at', '>', Carbon::now()->subHour());
    }

    public function active_matches()
    {
        return $this->hasMany(QmMatch::class)
            ->where('qm_matches.created_at', '>', Carbon::now()->subHour())
            ->where('qm_matches.updated_at', '>', Carbon::now()->subMinutes(2));
    }

    public function past_24_hours_matches()
    {
        return $this->hasMany(QmMatch::class)
            ->where('qm_matches.created_at', '>', Carbon::now()->subHours(24));
    }

    public function current_month_matches()
    {
        return $this->hasMany(QmMatch::class)
            ->where("updated_at", ">", Carbon::now()->startOfMonth())
            ->where("updated_at", "<", Carbon::now()->endOfMonth());
    }
}
