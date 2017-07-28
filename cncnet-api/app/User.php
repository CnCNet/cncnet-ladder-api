<?php namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

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

    public function usernames()
	{
		return $this->hasMany('App\Player');
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
}