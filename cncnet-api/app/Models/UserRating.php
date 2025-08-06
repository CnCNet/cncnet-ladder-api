<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRating extends Model
{
    public static $DEFAULT_RATING = 1200;

    protected $table = 'user_ratings';

    protected $fillable = [
        'user_id', 'ladder_id', 'rating', 'deviation', 'elo_rank', 'alltime_rank', 'rated_games', 'active',
    ];

    public $timestamps = true;

    public function save(array $options = [])
    {
        throw new \Exception("UserRating is read-only.");
    }

    public function delete()
    {
        throw new \Exception("UserRating is read-only.");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
