<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct()
    {
    }

    public function getNews(Request $request)
    {
        $news = News::orderBy("created_at", "DESC")->get();

        return view("news.index", [
            "news" => $news,
        ]);
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
