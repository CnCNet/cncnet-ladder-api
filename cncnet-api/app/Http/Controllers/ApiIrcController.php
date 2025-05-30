<?php namespace App\Http\Controllers;

use App\Models\Clan;
use App\Models\IrcAssociation;
use App\Models\IrcHostmask;
use App\Models\IrcPlayer;
use App\Models\Ladder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiIrcController extends Controller {

    public function getActive(Request $request, $abbreviation)
    {
        $ladder = Ladder::where('abbreviation', '=', $abbreviation)->first();

        if ($ladder === null)
        {
            return [];
        }

        $forgetSeconds  = 10;

        $lastRequest = Cache::get("getActive/{$ladder->id}");

        if ($lastRequest !== null && Carbon::now()->diff(new Carbon($lastRequest['cached_at']))->s > $forgetSeconds)
        {
            Cache::forget("getActive/{$ladder->id}");
        }

        $ladderId = $ladder->id;
        $ret = Cache::remember("getActive/{$ladderId}", 5 * 60, function() use($ladderId)
        {
            $hostmasks = IrcAssociation::loggedIn()->whereLadder($ladderId)->get();
            return [ 'check_back' => 5, 'cached_at' => (string)Carbon::now(), 'hostmasks' => $hostmasks ];
        });

        return $ret;
    }

    public function getPlayerNames(Request $request, $abbreviation)
    {
        $ladder = Ladder::where('abbreviation', '=', $abbreviation)->first();

        if ($ladder === null)
        {
            return [];
        }

        $players = Cache::remember("getPlayerNames/{$ladder->id}", 60 * 24, function() use($ladder)
        {
            return IrcPlayer::select('player_id as id', 'username')->where('ladder_id', '=', $ladder->id)->get();
        });

        return $players;
    }

    public function getHostmasks(Request $request)
    {
        return Cache::remember("getHostmasks", 60 * 24, function() { return IrcHostmask::select('id', 'value')->get(); } );
    }

    public function getClans(Request $request, $abbreviation)
    {
        $ladder = Ladder::where('abbreviation', '=', $abbreviation)->first();

        if ($ladder === null)
        {
            return [];
        }

        $clans = Cache::remember("getClans/{$ladder->id}", 30 * 60, function () use($ladder)
        {
            return Clan::select('id', 'short', 'name')->where('ladder_id', '=', $ladder->id)->get();
        });

        return $clans;
    }
}
