<?php

namespace App\Models;

use Carbon\Carbon;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    const God = "God";
    const Admin = "Admin";
    const Moderator = "Moderator";
    const User = "User";

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

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

    public function usernames()
    {
        return $this->hasMany('App\Models\Player');
    }

    public function ip()
    {
        return $this->belongsTo('App\Models\IpAddress', 'ip_address_id');
    }

    public function isAdmin()
    {
        return in_array($this->group, [self::God, self::Admin]);
    }

    public function isGod()
    {
        return in_array($this->group, [self::God]);
    }

    public function isModerator()
    {
        return in_array($this->group, [self::God, self::Admin, self::Moderator]);
    }

    public function bans()
    {
        return $this->hasMany("\App\Models\Ban");
    }

    public function bansGiven()
    {
        return $this->hasMany("\App\Models\Ban", "admin_id");
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

    public function ladderAdmins()
    {
        return $this->hasMany('App\Models\LadderAdmin');
    }

    public function ladders()
    {
        return $this->belongsToMany('App\Models\Ladder', 'ladder_admins');
    }

    public function privateLadders()
    {
        if ($this->isGod())
            return \App\Models\Ladder::where('private', '=', true);

        return $this->ladders()->where('private', '=', true);
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

    public function ipHistory()
    {
        return $this->hasMany('App\Models\IpAddressHistory');
    }
}
