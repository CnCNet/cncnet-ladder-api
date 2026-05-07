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

        // Check this user is allowed to set observer mode
        if (!$user->isObserver())
        {
            unset($userSettings["observer_mode"]);
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
            'allow_observers',
        ]), function ($value)
        {
            return $value !== null; // Include 0 in the filtered array
        }));

        // Handle observer_mode separately (string field, not integer)
        if ($user->isObserver())
        {
            $observerMode = $request->input('observer_mode');
            if (in_array($observerMode, ['observe_only', 'play_and_observe'])) {
                $requestData['observer_mode'] = $observerMode;
            } else {
                $requestData['observer_mode'] = null; // Default to play mode
            }
        }
        else
        {
            $requestData['observer_mode'] = null;
        }

        $userSettings->update($requestData);

        return $user->userSettings;
    }
}
