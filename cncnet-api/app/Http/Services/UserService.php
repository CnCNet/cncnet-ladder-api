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
    public function updateUserPreferencesFromRequest(Request $request, $user)
    {
        $userSettings = $user->userSettings;
        if ($userSettings === null)
        {
            $userSettings = new UserSettings();
            $userSettings->user_id = $user->id;
        }

        $requestData = array_map('intval', array_filter($request->only([
            'skip_score_screen',
            'match_any_map',
            'disabledPointFilter', // As column in database 
            'enableAnonymous', // As column in database 
            'match_ai',
            'is_observer',
            'allow_observers',
        ]), function ($value)
        {
            return $value !== null; // Include 0 in the filtered array
        }));

        $userSettings->update($requestData);

        return $user->userSettings;
    }
}
