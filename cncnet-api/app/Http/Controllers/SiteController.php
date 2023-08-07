<?php

namespace App\Http\Controllers;

use App\Http\Services\LadderService;
use App\News;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function getIndex(Request $request)
    {
        $ladderService = new LadderService();

        $news = News::orderBy("created_at", "desc")->limit(4)->get();

        return view("index", [
            "news" => $news,
            "ladders" => $ladderService->getLatestLadders(),
            "clan_ladders" => $ladderService->getLatestClanLadders(),
        ]);
    }

    public function getOBSHelp(Request $request)
    {
        return view("help.obs");
    }

    public function getClanLadderNews(Request $request)
    {
        return view("news.clans-coming-soon");
    }

    public function getDonate(Request $request)
    {
        return view("donate");
    }

    public function getStyleguide(Request $request)
    {
        return view("styleguide", ["ladders" => []]);
    }
}
