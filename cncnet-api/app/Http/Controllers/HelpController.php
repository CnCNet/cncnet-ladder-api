<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function getOBSHelp(Request $request)
    {
        return view("help.obs");
    }
}
