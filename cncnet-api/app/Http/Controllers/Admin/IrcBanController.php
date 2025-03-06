<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Irc\CreateIrcBanRequest;
use App\Http\Services\IrcBanService;
use App\Models\IrcBan;
use App\Models\IrcWarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IrcBanController extends Controller
{
    protected IrcBanService $ircBanService;

    public function __construct(
        IrcBanService $ircBanService,
    )
    {
        $this->ircBanService = $ircBanService;
    }

    public function getBanIndex()
    {
        $bans = IrcBan::limit(20)->orderBy("created_at", "desc")->get();
        $warnings = IrcWarning::limit(20)->orderBy("created_at", "desc")->get();
        return view('admin.irc.index', compact('bans', 'warnings'));
    }

    public function getAllBans()
    {
        $bans = IrcBan::orderBy("created_at", "desc")->paginate(20);
        return view('admin.irc.bans', compact('bans'));
    }

    public function getCreateBan(Request $request)
    {
        return view('admin.irc.create');
    }

    public function createBan(CreateIrcBanRequest $request)
    {
        // Safety checks
        if ($request->username == null && $request->ident == null && $request->host == null)
        {
            return redirect()->back()->withErrors(["You just banned everyone on the CnCNet server. Just kidding. Specify at least one value in the fields: user, ident or host"])->withInput();
        }

        if ($request->channel == null && $request->global_ban != "on")
        {
            return redirect()->back()->withErrors(["Which channel(s) are we banning this user from?"])->withInput();
        }

        $ircBan = $this->ircBanService->saveBan(
            banReason: $request->ban_reason,
            adminId: Auth::user()->id,
            channel: $request->channel,
            globalBan: $request->global_ban == "on",
            username: $request->username,
            ident: $request->ident,
            host: $request->host,
            expiresAt: $request->expires_at
        );

        return redirect()->route('admin.irc.bans.edit', ['id' => $ircBan->id])->with('status', 'Ban created');
    }

    public function getEditBan(Request $request)
    {
        $ban = IrcBan::findOrFail($request->id);
        return view('admin.irc.edit', compact('ban'));
    }

    public function updateBan(Request $request)
    {
        $ban = IrcBan::findOrFail($request->ban_id);

        // Safety checks
        if ($request->username == null && $request->ident == null && $request->host == null)
        {
            return redirect()->back()->withErrors(["You just banned everyone on the CnCNet server. Just kidding. Specify at least one value in the fields: username, ident or host"])->withInput();
        }

        if ($request->channel == null && $request->global_ban != "on")
        {
            return redirect()->back()->withErrors(["Which channel(s) are we banning this user from?"])->withInput();
        }

        $this->ircBanService->updateBan(
            banId: $ban->id,
            banReason: $request->ban_reason,
            adminId: Auth::user()->id,
            channel: $request->channel,
            globalBan: $request->global_ban == "on",
            expiresAt: $request->expires_at
        );

        return redirect()->back()->with('status', 'Ban updated');
    }

    public function expireBan(Request $request)
    {
        $ban = IrcBan::findOrFail($request->ban_id);
        $this->ircBanService->expireBan($ban, Auth::user()->id);
        return redirect()->back()->with('status', 'Ban expired');
    }
}
