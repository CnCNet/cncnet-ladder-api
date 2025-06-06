<?php

namespace App\Http\Controllers\Api\V2\Bans;

use App\Http\Controllers\Controller;
use App\Http\Services\AuthService;
use App\Http\Services\LadderService;
use App\Http\Services\PlayerService;
use App\Models\IrcIpAddress;
use App\Models\IrcIpAddressHistory;
use App\Models\IrcUser;
use Exception;
use Illuminate\Http\Request;

class ApiBanController extends Controller
{
    public function checkIn(Request $request)
    {
        try
        {
            $ircUser = IrcUser::where("ident", $request->username)->where("ident", $request->ident)->first();
            if ($ircUser == null)
            {
                $ircUser = new IrcUser();
                $ircUser->ident = $request->ident;
                $ircUser->username = $request->username;
                $ircUser->host = $request->host;
                $ircUser->save();
            }

            $ip = isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $request->getClientIp();

            $ircIpAddressId = IrcIpAddress::getID($ip);
            IrcIpAddressHistory::addHistory($ircUser->id, $ircIpAddressId);

            // Check for bans, return code

            return $ircUser->bans;
        }
        catch (Exception $ex)
        {
            dd($ex);
        }
    }
}
