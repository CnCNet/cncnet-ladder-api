<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Side extends Model {

    use HasFactory;

    protected $fillable = [
        'ladder_id',
        'local_id',
        'name'
    ];

	//
    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }
}
