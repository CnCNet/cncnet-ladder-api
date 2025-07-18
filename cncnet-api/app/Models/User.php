<?php

namespace App\Models;

use App\Http\Services\UserRatingService;
use App\Notifications\Auth\ResetPasswordNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract, JWTSubject
{
    use Authenticatable, CanResetPassword, Notifiable;

    const God = "God";
    const Admin = "Admin";
    const Moderator = "Moderator";
    const Observer = "Observer";
    const User = "User";

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password', 'email_verified',];

    protected $hidden = ['password', 'remember_token'];

    public function isAdmin()
    {
        return in_array($this->group, [self::God, self::Admin]);
    }

    public function isGod()
    {
        return in_array($this->group, [self::God]);
    }

    /**
     * Is user allowed to observe games? 
     * This is different than userSettings->is_observer where the user turns it on and only if they are allowed to.
     */
    public function isObserver()
    {
        return in_array($this->group, [self::God, self::Admin, self::Moderator, self::Observer]);
    }

    public function isModerator()
    {
        return in_array($this->group, [self::God, self::Admin, self::Moderator]);
    }

    public function isNewsAdmin()
    {
        if ($this->isGod())
            return true;

        $ladderAdmin = $this->ladderAdmins()->where("user_id", $this->id)
            ->where("admin", true)
            ->orWhere("moderator", true)
            ->first();

        return $ladderAdmin->moderator || $ladderAdmin->admin;
    }

    public function isLadderAdmin($ladder)
    {
        if ($this->isGod())
            return true;

        $la = $this->ladderAdmins()->where('ladder_id', '=', $ladder->id)->first();
        if ($la === null)
            return false;

        return $la->admin;
    }

    public function isLadderMod($ladder)
    {
        if ($this->isGod())
            return true;

        $la = $this->ladderAdmins()->where('ladder_id', '=', $ladder->id)->first();
        if ($la === null)
            return false;

        return $la->moderator;
    }

    public function isLadderTester($ladder)
    {
        $la = $this->ladderAdmins()->where('ladder_id', '=', $ladder->id)->first();
        if ($la === null)
            return false;

        return $la->tester;
    }

    public function isConfirmedPrimary(): bool
    {
        return !empty($this->alias) || $this->primary_user_id === $this->id;
    }

    public function isUnconfirmedPrimary(): bool
    {
        return $this->primary_user_id === null && empty($this->alias);
    }

    public function isDuplicate(): bool
    {
        return $this->primary_user_id !== null && $this->primary_user_id != $this->id;
    }

    public function hasDuplicates(): bool
    {
        return User::where('primary_user_id', $this->id)->where('id', '!=', $this->id)->exists();
    }

    public function accountType() : string
    {
        if ($this->isConfirmedPrimary())
        {
            return "Primary";
        }
        else if ($this->isUnconfirmedPrimary())
        {
            return "Unconfirmed primary";
        }
        else if ($this->isDuplicate())
        {
            $primary = User::find($this->primary_user_id);
            if ($primary)
            {
                $text = 'Duplicate of #' . $primary->id . ' (';
                $text .= $primary->alias ?: $primary->name;
                $text .= ')';
                return $text;
            }
            else
            {
                $text = 'Duplicate of #' . $this->primary->id . ' (Unknown user)';
            }
        }

        return "Unknown";
    }

    public function canEditAnyLadders()
    {
        if ($this->isGod())
            return true;

        $la = $this->ladderAdmins()->where(
            function ($query)
            {
                $query->where('admin', '=', true)->orWhere('moderator', '=', true);
            }
        );

        return $la->count() > 0;
    }

    public function getBan($start = false)
    {
        $bestBan = null;
        foreach ($this->bans as $ban)
        {
            $ban->checkStartBan($start);
        }
        $bestBan = $this->bans()->where('expires', '>', Carbon::now())->orderBy('expires', 'ASC')->first();

        if ($bestBan !== null)
            return $bestBan->checkStartBan($start);

        return null;
    }

    public function checkForShadowBan($ip, $qmClientId)
    {
        try
        {
            $currentDateTime = Carbon::now();

            $userIds[] = $this->id;
            $userIds = QmUserId::where("qm_user_id", $qmClientId)->pluck("user_id")->toArray();

            $shadowBans = Ban::whereIn("user_id", $userIds)
                ->where("ban_type", Ban::BAN_SHADOW)
                ->where("expires", ">", $currentDateTime)
                ->count();

            if ($shadowBans > 0)
            {
                return true;
            }

            $users = IpAddress::findByIP($ip)->users;
            foreach ($users as $user)
            {
                $shadowBans = Ban::where("user_id", $user->id)
                    ->where("ban_type", Ban::BAN_SHADOW)
                    ->where("expires", ">", $currentDateTime)
                    ->count();

                if ($shadowBans > 0)
                {
                    return true;
                }
            }
        }
        catch (Exception $ex)
        {
            Log::info("Error checking for shadow ban: " . $ex->getMessage());
        }

        return false;
    }

    public function privateLadders()
    {
        if ($this->isGod())
            return Ladder::where('private', '=', true);

        return $this->ladders()->where('private', '=', true);
    }

    public function verificationSent()
    {
        $nextUpdate = Carbon::now()->subHour(1);
        return EmailVerification::where('user_id', '=', $this->id)->where('created_at', '>', $nextUpdate)->count() > 0;
    }

    public function sendNewVerification()
    {
        // Delete old verification table entry
        $old = EmailVerification::where('user_id', '=', $this->id)->get();
        foreach ($old as $v)
        {
            $v->delete();
        }

        // Create a new confirmation entry
        $ev = new EmailVerification;
        $ev->user_id = $this->id;
        $ev->token = hash('sha256', rand(0, getrandmax()) . $this->email);
        $ev->save();

        $email = $this->email;
        // Email new confirmation
        Mail::send('emails.verification', ['token' => $ev->token], function ($message) use ($email)
        {
            $message->to($email)->subject('Email verification for CnCNet Ladder');
        });
        return true;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getUserAvatar()
    {
        if ($this->avatar_path && !$this->userSettings?->is_anonymous)
        {
            if (config("app.env") !== "production")
            {
                return "https://ladder.cncnet.org/" . $this->avatar_path;
            }
            return asset($this->avatar_path, true);
        }
        return null;
    }

    public function removeAvatar()
    {
        if ($this->avatar_path)
        {
            try
            {
                Storage::delete($this->avatar_path);
            }
            catch (Exception $ex)
            {
            }
        }

        $this->avatar_path = null;
        $this->save();
    }

    public function restrictAvatarUpload($bool)
    {
        $this->avatar_upload_allowed = $bool;
        $this->save();
    }

    public function updateAlias($alias)
    {
        // Clear cache
        if ($this->alias)
        {
            Cache::forget("admin/users/users/{$this->alias}");
        }
        Cache::forget("admin/users/users/{$this->id}");

        $this->alias = $alias;
        $this->save();
    }

    public function getIsAllowedToUploadAvatarOrEmoji()
    {
        return $this->avatar_upload_allowed;
    }

    public function getIsAllowedToChat()
    {
        return $this->chat_allowed;
    }

    public function getDiscordProfile()
    {
        return $this->discord_profile;
    }

    public function getYouTubeProfile()
    {
        if ($this->youtube_profile)
        {
            return "https://youtube.com/$this->youtube_profile";
        }
        return null;
    }

    public function getTwitchProfile()
    {
        if ($this->twitch_profile)
        {
            return "https://twitch.tv/$this->twitch_profile";
        }
        return null;
    }


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Returns live user rating or creates a new one if one doesn't exist yet
     * @return mixed 
     */
    public function getOrCreateLiveUserRating()
    {
        $userRating = UserRating::where("user_id", "=", $this->id)->first();
        if ($userRating == null)
        {
            $userRating = UserRating::createNewFromLegacyPlayerRating($this);
            Log::info("User ** getUserRating - Rating null creating new. UserId: " . $this->id  . " Created new user rating: " . $userRating);
        }
        return $userRating;
    }

    /**
     * This will give you the users "live" tier calculation
     * @param mixed $history 
     * @return int 
     */
    public function getLiveUserTier($history)
    {
        $userRating = $this->getOrCreateLiveUserRating();
        return UserRatingService::getTierByLadderRules($userRating->rating, $history->ladder);
    }


    /**
     * 
     * @param mixed $ladder 
     * @return mixed 
     */
    public function getUserLadderTier($ladder)
    {
        $userTier = $this->userTier()
            ->where("user_id", $this->id)
            ->where("ladder_id", $ladder->id)
            ->first();

        if ($userTier == null)
        {
            $tier = null;
            $userRating = $this->userRating;

            if ($userRating)
            {
                $tier = UserRatingService::getTierByLadderRules($userRating->rating, $ladder);
            }
            else
            {
                $tier = UserRatingService::getTierByLadderRules(UserRating::$DEFAULT_RATING, $ladder);
            }

            $userTier = UserTier::createNew($this->id, $ladder->id, $tier);
        }

        return $userTier;
    }

    public function userSince()
    {
        return $this->created_at->diffForHumans();
    }

    public function canUserPlayBothTiers($ladder)
    {
        $userTier = $this->getUserLadderTier($ladder);
        return $userTier->both_tiers;
    }


    /**
     * Returns true when the user only wants pro only matchups
     * @return bool 
     */
    public function getProOnlyMatchupsPreference(): bool
    {
        return $this->userSettings->getProOnlyMatchups();
    }

    public function getEmoji(): ?string
    {
        if ($this->emoji)
        {
            return json_decode($this->emoji);
        }
        return null;
    }

    # Relationships
    public function userTier()
    {
        return $this->hasMany(UserTier::class, 'user_id');
    }

    public function userSettings()
    {
        return $this->hasOne(UserSettings::class, 'user_id');
    }

    public function achievements()
    {
        return $this->hasMany(AchievementProgress::class, 'user_id');
    }

    public function userRating()
    {
        return $this->hasOne(UserRating::class, 'user_id');
    }

    public function ipHistory()
    {
        return $this->hasMany(IpAddressHistory::class);
    }

    public function ladderAdmins()
    {
        return $this->hasMany(LadderAdmin::class);
    }

    public function ladders()
    {
        return $this->belongsToMany(Ladder::class, 'ladder_admins');
    }

    public function bans()
    {
        return $this->hasMany(Ban::class);
    }

    public function bansGiven()
    {
        return $this->hasMany(Ban::class, "admin_id");
    }

    public function usernames()
    {
        return $this->hasMany(Player::class);
    }

    public function ip()
    {
        return $this->belongsTo(IpAddress::class, 'ip_address_id');
    }
}
