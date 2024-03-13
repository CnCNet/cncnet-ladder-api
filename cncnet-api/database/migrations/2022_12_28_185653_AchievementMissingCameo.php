<?php

use Illuminate\Database\Migrations\Migration;

class AchievementMissingCameo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $a = \App\Models\Achievement::where('cameo', 'shkticon')->get();
        $a->map(function ($ach) {
            $ach->cameo = 'shkicon';
            $ach->save();
            return $ach;
        });

        $a = \App\Models\Achievement::where('cameo', 'terroricon')->get();
        $a->map(function ($ach) {
            $ach->cameo = 'trsticon';
            $ach->save();
            return $ach;
        });

        $a = \App\Models\Achievement::where('cameo', 'e1icon')->get();
        $a->map(function ($ach) {
            $ach->cameo = 'giicon';
            $ach->save();
            return $ach;
        });

        $a = \App\Models\Achievement::where('cameo', 'gosticon')->get();
        $a->map(function ($ach) {
            $ach->cameo = 'sealicon';
            $ach->save();
            return $ach;
        });

        $a = \App\Models\Achievement::where('cameo', 'orcaicon')->get();
        $a->map(function ($ach) {
            $ach->cameo = 'falcicon';
            $ach->save();
            return $ach;
        });
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
