<?php

use App\Models\SpawnOption;
use App\Models\SpawnOptionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSpawnOptionsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spawn_options', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('type_id');
            $table->integer('name_id');
            $table->integer('string1_id');
            $table->integer('string2_id');
            $table->timestamps();
        });
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "GameSpeed", "Settings", "GameSpeed")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Seed", "Settings", "Seed")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Credits", "Settings", "Credits")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "UnitCount", "Settings", "UnitCount")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Host", "Settings", "Host")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "TechLevel", "Settings", "TechLevel")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Firestorm", "Settings", "Firestorm")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Ra2Mode", "Settings", "Ra2Mode")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "ShortGame", "Settings", "ShortGame")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "MultiEngineer", "Settings", "MultiEngineer")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "MCVRedeploy", "Settings", "MCVRedeploy")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Crates", "Settings", "Crates")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Bases", "Settings", "Bases")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "GameMode", "Settings", "GameMode")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "HarvesterTruce", "Settings", "HarvesterTruce")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "AlliesAllowed", "Settings", "AlliesAllowed")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "BridgeDestroy", "Settings", "BridgeDestroy")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "FogOfWar", "Settings", "FogOfWar")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "BuildOffAlly", "Settings", "BuildOffAlly")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "MultipleFactory", "Settings", "MultipleFactory")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "AimableSams", "Settings", "AimableSams")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "AttackNeutralUnits", "Settings", "AttackNeutralUnits")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Superweapons", "Settings", "Superweapons")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "OreRegenerates", "Settings", "OreRegenerates")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Aftermath", "Settings", "Aftermath")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "FixAIAlly", "Settings", "FixAIAlly")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "AllyReveal", "Settings", "AllyReveal")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "AftermathFastBuildSpeed", "Settings", "AftermathFastBuildSpeed")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "ParabombsInMultiplayer", "Settings", "ParabombsInMultiplayer")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "FixFormationSpeed", "Settings", "FixFormationSpeed")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "FixMagicBuild", "Settings", "FixMagicBuild")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "FixRangeExploit", "Settings", "FixRangeExploit")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "SuperTeslaFix", "Settings", "SuperTeslaFix")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "ForcedAlliances", "Settings", "ForcedAlliances")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "TechCenterBugFix", "Settings", "TechCenterBugFix")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "NoScreenShake", "Settings", "NoScreenShake")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "NoTeslaZapEffectDelay", "Settings", "NoTeslaZapEffectDelay")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "DeadPlayersRadar", "Settings", "DeadPlayersRadar")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "CaptureTheFlag", "Settings", "CaptureTheFlag")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "SlowUnitBuild", "Settings", "SlowUnitBuild")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "ShroudRegrows", "Settings", "ShroudRegrows")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "AIPlayers", "Settings", "AIPlayers")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Tournament", "Settings", "Tournament")->save();
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "FrameSendRate", "Settings", "FrameSendRate")->save();
        SpawnOption::makeOne(SpawnOptionType::PREPEND_FILE, "Prepend To Spawnmap.ini", "spawnmap.ini", "")->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('spawn_options');
    }
}
