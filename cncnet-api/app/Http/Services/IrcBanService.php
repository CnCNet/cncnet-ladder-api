<?php

namespace App\Http\Services;

use App\Models\IrcBan;
use App\Models\IrcBanLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class IrcBanService
{
    public function __construct()
    {
    }

    public function saveBan(
        string $banReason,
        string $adminId,
        string|null $channel,
        bool|null $globalBan,
        string|null $username,
        string|null $ident,
        string|null $host,
        string|null $expiresAt
    ): IrcBan
    {
        $ircBan = new IrcBan();

        $ircBan->ident = $ident;
        $ircBan->host = $host;
        $ircBan->username = $username;

        $ircBan->admin_id = $adminId;
        $ircBan->channel = $channel;
        $ircBan->global_ban = $globalBan ?? false;
        $ircBan->ban_reason = $banReason;
        $ircBan->ban_original_expiry = $expiresAt;
        $ircBan->expires_at = $expiresAt;

        $ircBan->save();

        $this->saveLog($ircBan->id, $adminId, IrcBanLog::ACTION_CREATED);

        return $ircBan;
    }

    public function updateBan(
        string $banId,
        string $banReason,
        string $adminId,
        string|null $channel,
        bool|null $globalBan,
        string|null $expiresAt
    ): void
    {
        $ircBan = IrcBan::find($banId);

        $ircBan->admin_id = $adminId;
        $ircBan->channel = $channel;
        $ircBan->global_ban = $globalBan ?? false;
        $ircBan->ban_reason = $banReason;
        $ircBan->expires_at = $expiresAt;
        $ircBan->save();

        $this->saveLog($ircBan->id, $adminId, IrcBanLog::ACTION_UPDATED);
    }

    public function expireBan(IrcBan $ban, string $adminId): void
    {
        $this->saveLog(
            banId: $ban->id,
            adminId: $adminId,
            action: IrcBanLog::ACTION_EXPIRED,
        );

        $ban->expires_at = Carbon::now();
        $ban->save();
    }

    private function saveLog(
        string $banId,
        string $adminId,
        string $action,
    ): IrcBanLog
    {
        $ircLog = new IrcBanLog();
        $ircLog->ban_id = $banId;
        $ircLog->admin_id = $adminId;
        $ircLog->action = $action;
        $ircLog->save();
        return $ircLog;
    }

    public function getActiveBans()
    {
        $now = Carbon::now();
        $activeBans = IrcBan::select(["ident", "host", "username", "channel", "expires_at"])->get();

        foreach ($activeBans as $ban)
        {
            $ban->has_expired = $ban->expires_at ? $ban->expires_at->isBefore($now) : false;
        }

        return response()->json($activeBans);
    }
}
