<?php

namespace App\Http\Controllers;

use App\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct()
    {
    }

    public function getNewsBySlug(Request $request, $slug)
    {
        $news = News::where("slug", $slug)->first();
        if ($news == null)
        {
            abort(404);
        }

        $moreNews = News::where("id", "!=", $news->id)->limit(4)->get();

        return view("news.detail", [
            "news" => $news,
            "moreNews" => $moreNews
        ]);
    }
}
