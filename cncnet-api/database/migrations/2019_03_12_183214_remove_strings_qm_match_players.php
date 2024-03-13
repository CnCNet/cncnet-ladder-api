<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class RemoveStringsQmMatchPlayers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('player_data_strings', function(Blueprint $table)
        {
            //For use with ddraw, version, and platform
            $table->increments('id');
            $table->string('value');
        });

        Schema::create('map_side_strings', function(Blueprint $table)
        {
            $table->increments('id');
            $table->binary('value', 500);
        });

		Schema::table('qm_match_players', function(Blueprint $table)
		{
			//
            $table->integer('ip_address_id')->unsigned()->nullable();
            $table->integer('ipv6_address_id')->unsigned()->nullable();
            $table->integer('lan_address_id')->unsigned()->nullable();

            $table->integer('version_id')->unsigned()->nullable();
            $table->integer('platform_id')->unsigned()->nullable();
            $table->integer('map_sides_id')->unsigned()->nullable();

            $table->integer('ddraw_id')->unsigned()->nullable();

		});
        \App\Models\QmMatchPlayer::chunk(500, function($qmPlayers) {
            foreach ($qmPlayers as $qp)
            {
                $ip = \App\Models\IpAddress::findByIp($qp->ip_address);
                if ($ip !== null)
                    $qp->ip_address_id = $ip->id;

                $ip = \App\Models\IpAddress::findByIp($qp->ipv6_address);
                if ($ip !== null)
                    $qp->ipv6_address_id = $ip->id;

                $ip = \App\Models\IpAddress::findByIp($qp->lan_ip);
                if ($ip !== null)
                    $qp->lan_address_id = $ip->id;

                $v = \App\Models\PlayerDataString::findValue($qp->version);
                if ($v !== null)
                    $qp->version_id = $v->id;

                $v = \App\Models\PlayerDataString::findValue($qp->platform);
                if ($v !== null)
                    $qp->platform_id = $v->id;

                $ms = \App\Models\MapSideString::findValue($qp->map_sides);
                if ($ms !== null)
                    $qp->map_sides_id = $ms->id;

                $qp->save();
            }
        });

        Schema::table('qm_match_players', function(Blueprint $table)
		{
            $table->dropColumn('ip_address');
            $table->dropColumn('ipv6_address');
            $table->dropColumn('lan_ip');
            $table->dropColumn('version');
            $table->dropColumn('platform');
            $table->dropColumn('map_sides');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{

        Schema::table('qm_match_players', function(Blueprint $table)
		{
			//
            $table->string('ip_address')->nullable();
            $table->string('ipv6_address')->nullable();
            $table->string('lan_ip')->nullable();
            $table->string('version')->nullable();
            $table->string('platform')->nullable();
            $table->text('map_sides')->nullable();
		});

        $qmPlayers = \App\Models\QmMatchPlayer::all();
        foreach ($qmPlayers as $qp)
        {
            $qp->ip_address = $qp->ipAddress ? $qp->ipAddress->address : "";
            $qp->ipv6_address = $qp->ipv6Address ? $qp->ipv6Address->address : "";
            $qp->lan_ip = $qp->lanAddress ? $qp->lanAddress->address : "";
            $qp->version = $qp->version()->first()->value;
            $qp->platform = $qp->platform()->first()->value;
            $qp->map_sides = $qp->mapSides->value;

            $qp->save();
        }

		Schema::table('qm_match_players', function(Blueprint $table)
		{
			//
            $table->dropColumn('ip_address_id');
            $table->dropColumn('ipv6_address_id');
            $table->dropColumn('lan_address_id');
            $table->dropColumn('version_id');
            $table->dropColumn('platform_id');
            $table->dropColumn('map_sides_id');
            $table->dropColumn('ddraw_id');
		});

        Schema::drop('player_data_strings');
        Schema::drop('map_side_strings');
	}

}
