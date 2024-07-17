<?php

namespace App\Http\Services;

use App\Models\IrcBan;
use App\Models\IrcBanLog;
use App\Models\IrcWarning;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class IrcWarningService
{
    public function __construct()
    {
    }

    public function issueWarning(string $adminId, string|null $username, string|null $ident, string $warningMessage, string $channel)
    {
        $ircWarning = new IrcWarning();
        $ircWarning->admin_id = $adminId;
        $ircWarning->username = $username;
        $ircWarning->ident = $ident;
        $ircWarning->warning_message = $warningMessage;
        $ircWarning->channel = $channel;
        $ircWarning->save();
        return $ircWarning;
    }

    public function getActiveWarnings()
    {
        return IrcWarning::where("acknowledged", false)->select(["ident", "username", "channel", "warning_message"])->get();
    }
}
