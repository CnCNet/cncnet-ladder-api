<?php

namespace App\Http\Controllers\Api\V2\Events;

use App\Http\Controllers\Controller;
use App\Models\User;

class ApiEventController extends Controller
{
    public function __construct()
    {
    }

    public function getEvents()
    {
        // @TODO: Hook up to an events admin page for super admins for future events
        // Temp hack for now until we work this out properly
        $mj = User::find(9466);
        $matt = User::find(30045);
        $doof = User::find(38417);

        if ($mj->userSettings->is_observer == true)
        {
            return [
                "live" => true,
                "url" => "https://www.twitch.tv/mj_vst"
            ];
        }

        if ($matt->userSettings->is_observer == true)
        {
            return [
                "live" => true,
                "url" => "https://www.twitch.tv/fortuneschaos"
            ];
        }

        if ($doof->userSettings->is_observer == true)
        {
            return [
                "live" => true,
                "url" => "https://www.twitch.tv/doof88"
            ];
        }

        return [
            "live" => false,
            "url" => null
        ];
    }
}
