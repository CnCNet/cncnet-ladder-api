<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model 
{
    protected $table = 'games';

    protected $fillable = ['wol_gid', 'duration', 'afps', 'crates', 'oosy', 'bases', 'units', 'tech'];

    protected $hidden = ['created_at', 'updated_at'];
}