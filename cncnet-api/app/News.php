<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class News extends Model
{
    public function getAuthor()
    {
        return $this->belongsTo('App\User', "author_id");
    }

    public function getFeaturedImagePath()
    {
        if ($this->featured_image)
        {
            if (config("app.env") !== "production")
            {
                return asset($this->featured_image, false);
            }
            return asset($this->featured_image, true);
        }
        return null;
    }
}
