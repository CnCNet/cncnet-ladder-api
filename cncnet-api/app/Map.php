<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Map extends Model 
{
    protected $table = 'maps';
    protected $fillable = ['name'];
    protected $hidden = ['created_at', 'updated_at'];
}