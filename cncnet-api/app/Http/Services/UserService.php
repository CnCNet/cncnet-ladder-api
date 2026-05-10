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
        // Only update if explicitly provided in request to prevent partial updates from wiping the setting
        if ($user->isObserver())
        {
            if ($request->has('observer_mode')) {
                $observerMode = $request->input('observer_mode');
                $oldValue = $userSettings->observer_mode;

                if (in_array($observerMode, ['play', 'observe_only', 'play_and_observe'])) {
                    $requestData['observer_mode'] = $observerMode;
                } else {
                    $requestData['observer_mode'] = null; // Default/fallback for invalid values
                }

                // Log if value changed
                if ($oldValue !== $requestData['observer_mode']) {
                    \Illuminate\Support\Facades\Log::info('Observer mode changed via API', [
                        'user_id' => $user->id,
                        'old_value' => $oldValue,
                        'new_value' => $requestData['observer_mode']
                    ]);
                }
            }
            // If not in request, preserve existing value (don't modify)
        }
        else
        {
            // User doesn't have observer permission, force to null
            if ($userSettings->observer_mode !== null) {
                \Illuminate\Support\Facades\Log::info('Observer mode cleared - user lost observer permission', [
                    'user_id' => $user->id,
                    'old_value' => $userSettings->observer_mode
                ]);
            }
            $requestData['observer_mode'] = null;
        }

        $userSettings->update($requestData);

        return $user->userSettings;
    }
}
