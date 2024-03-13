<?php namespace App\Providers;

use App\Models\Clan;
use App\Models\ClanPlayer;
use App\Models\IrcHostmask;
use App\Models\IrcPlayer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class IrcCache extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //

        IrcHostmask::created(function($hm)
        {
            Cache::forget("getHostmasks");
        });

        IrcPlayer::saving(function($ip)
        {
            if ($ip->isDirty('username'))
                Cache::forget("getPlayerNames/{$ip->ladder_id}");
        });


        /* Caching is done by IrcController
        IrcAssociation::saving(function($ia)
        {
            if ($ia->isDirty('clan_id') || $ia->isDirty('player_id') || $ia->isDirty('irc_hostmask_id'))
            {
                Cache::forget("getActive/{$ia->ladder_id}");
            }
        });*/

        ClanPlayer::saved(function($clanPlayer)
        {
            if ($clanPlayer->player->ircAssociation !== null)
            {
                $clanPlayer->player->ircAssociation->clan_id = $clanPlayer->clan_id;
                $clanPlayer->player->ircAssociation->save();
            }
        });

        ClanPlayer::deleting(function($clanPlayer)
        {
            if ($clanPlayer->player->ircAssociation !== null)
            {
                $clanPlayer->player->ircAssociation->clan_id = 0;
                $clanPlayer->player->ircAssociation->save();
            }
        });

        Clan::saved(function($clan)
        {
            Cache::forget("getClans/{$clan->ladder_id}");
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
