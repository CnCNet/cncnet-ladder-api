<?php

use App\Models\Ladder;
use App\Models\UserTier;
use Illuminate\Database\Migrations\Migration;

class DeleteDuplicateUserTiers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Delete duplicate user_id & ladder_id
        $ladders = Ladder::where("id", "!=", null)->pluck("id");
        $userIds = UserTier::where("user_id", "!=", null)->pluck("user_id");
        foreach ($ladders as $ladderId)
        {
            foreach ($userIds as $userId)
            {
                $results = UserTier::where("ladder_id", $ladderId)
                    ->where("user_id", $userId)
                    ->get();

                if (count($results) > 1)
                {
                    // Keep one 
                    $keep = $results[0];
                    UserTier::where("ladder_id", $ladderId)
                        ->where("user_id", $userId)
                        ->where("id", "!=", $keep->id)
                        ->delete();
                }
            }
        }
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
