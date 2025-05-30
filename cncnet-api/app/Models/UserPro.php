<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPro extends Model
{
    protected $fillable = [
        'ladder_id',
        'user_id',
    ];

    public function __construct()
    {
    }

    # Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }
}
