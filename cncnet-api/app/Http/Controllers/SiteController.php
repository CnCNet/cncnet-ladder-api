<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SiteController extends Controller
{
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
