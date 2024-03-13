<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

class CreateMissingUserSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = \App\Models\User::select('users.id')->leftJoin('user_settings', 'users.id', '=', 'user_settings.user_id')->whereNull('user_settings.user_id');
        Log::info('num users missing user settings ' . $users->count());

        foreach ($users->get() as $user)
        {
            $userSettings = new \App\Models\UserSettings();
            $userSettings->user_id = $user->id;
            $userSettings->save();
        }

        Log::info('num users missing user settings ' . $users->count());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
