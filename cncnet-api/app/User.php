<?php namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Carbon\Carbon;
use Mail;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
	use Authenticatable, CanResetPassword;

    const God = "God";
    const Admin = "Admin";
    const Moderator = "Moderator";
    const User = "User";

	protected $table = 'users';

	protected $fillable = ['name', 'email', 'password'];

	protected $hidden = ['password', 'remember_token'];

    public function canEditAnyLadders()
    {
        if ($this->isGod())
            return true;

        $la = $this->ladderAdmins()->where(
            function($query)
            {
                $query->where('admin', '=', true)->orWhere('moderator', '=', true);
            });

        return $la->count() > 0;
    }

    public function usernames()
	{
		return $this->hasMany('App\Player');
	}

    public function ip()
    {
        return $this->belongsTo('App\IpAddress','ip_address_id');
    }

    public function isAdmin()
    {
        return in_array(\Auth::user()->group, [self::God, self::Admin]);
    }

    public function isGod()
    {
        return in_array(\Auth::user()->group, [self::God]);
    }

    public function isModerator()
    {
        return in_array(\Auth::user()->group, [self::God, self::Admin, self::Moderator]);
    }

    public function bans()
    {
        return $this->hasMany("\App\Ban");
    }

    public function bansGiven()
    {
        return $this->hasMany("\App\Ban", "admin_id");
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
        return $this->hasMany('App\LadderAdmin');
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
        return EmailVerification::where('user_id', '=', $this->id)->count() > 0;
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
        $ev->token = hash('sha256', rand(0, getrandmax()).$this->email);
        $ev->save();

        $email = $this->email;
        // Email new confirmation
        Mail::send('emails.verification', ['token' => $ev->token ], function($message) use ($email)
        {
            $message->to($email)->subject('Email verification for CnCNet Ladder');
        });
        return true;
    }
}
