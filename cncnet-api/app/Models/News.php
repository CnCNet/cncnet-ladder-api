<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    public function getAuthor()
    {
        return $this->belongsTo(User::class, "author_id");
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
