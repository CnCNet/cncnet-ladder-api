<?php

namespace App\Http\Services;

use App\UserSettings;
use Illuminate\Http\Request;

class UserService
{
    public function __construct()
    {
    }


    /**
     * 
     * @param Request $request 
     * @param mixed $user 
     * @return \App\UserSettings 
     */
    public function updateUserPreferences(Request $request, $user)
    {
        $userSettings = $user->userSettings;
        if ($userSettings === null)
        {
            $userSettings = new UserSettings();
            $userSettings->user_id = $user->id;
        }

        // Update only the keys that exist in the request
        $userSettings->update($request->only([
            'skip_score_screen',
            'match_any_map',
            'disabledPointFilter',
            'enableAnonymous',
            'match_ai',
            'is_observer',
            'allow_observers',
        ]));

        return $userSettings;
    }
}
