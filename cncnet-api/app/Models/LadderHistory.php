<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LadderHistory extends Model
{
    use HasFactory;

    protected $table = 'ladder_history';
    protected $casts = [
        'starts' => 'datetime',
        'ends' => 'datetime'
    ];

    protected $fillable = [
        'ladder_id',
        'starts',
        'ends',
        'short',
    ];

    public $timestamps = false;

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function hasEnded()
    {
        return $this->ends < Carbon::now();
    }
}
