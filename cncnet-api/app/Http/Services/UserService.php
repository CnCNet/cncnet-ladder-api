<?php

namespace App\Http\Services;

use App\Models\UserSettings;
use Illuminate\Http\Request;

class UserService
{
    public function __construct()
    {
    }


    public function getUserPreferences($user)
    {
        $userSettings = $user->userSettings;

        // Check this user is allowed to set this
        if (!$user->isObserver())
        {
            unset($userSettings["is_observer"]);
        }

        return $userSettings;
    }

    /**
     * 
     * @param Request $request 
     * @param mixed $user 
     * @return \App\Models\UserSettings
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
            'is_anonymous', // As column in database 
            'match_ai',
            'is_observer',
            'allow_observers',
        ]), function ($value)
        {
            return $value !== null; // Include 0 in the filtered array
        }));

        // Check this user is allowed to set this
        if (!$user->isObserver())
        {
            $requestData["is_observer"] = 0;
        }

        $userSettings->update($requestData);

        return $user->userSettings;
    }
}
