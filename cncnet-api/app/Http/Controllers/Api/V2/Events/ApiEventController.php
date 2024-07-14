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
        $mj = User::where("email", "mj24140@gmail.com")->first();

        return ["live" => $mj->userSettings->is_observer == true];
    }
}
