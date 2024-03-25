<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DuneSpawnOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWN_INI, "Settings.DisableEngineer", "Settings", "DisableEngineer")->save(); // Yes
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWN_INI, "Settings.DisableTurrets", "Settings", "DisableTurrets")->save(); // Yes
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWN_INI, "Settings.Worms", "Settings", "Worms")->save(); // 0
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWN_INI, "Settings.Handicap", "Settings", "Handicap")->save(); // 0
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWN_INI, "Settings.MaxAhead", "Settings", "MaxAhead")->save(); // 125
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWN_INI, "Settings.NoCarryall", "Settings", "NoCarryall")->save(); // Yes


        // [Debug]
        // ExeHash=auVduCLE+NR2dCa9te+6aWA6s3o=
        // ExeLastWriteTime=2024/3/13 00:59:18
        // OSVersion=Microsoft Windows NT 6.2.9200.0, Revision 0
        // MapHash=2558fe4c90826b5584da6327dd6f6b13666481f2
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
