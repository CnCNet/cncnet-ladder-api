<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Irc\CreateIrcBanRequest;
use App\Http\Services\IrcBanService;
use App\Http\Services\IrcWarningService;
use App\Models\IrcBan;
use App\Models\IrcWarning;
use App\Rules\AtLeastOneField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IrcBanController extends Controller
{
    protected IrcBanService $ircBanService;
    protected IrcWarningService $ircWarningService;

    public function __construct(
        IrcBanService $ircBanService,
        IrcWarningService $ircWarningService
    )
    {
        $this->ircBanService = $ircBanService;
        $this->ircWarningService = $ircWarningService;
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

    public function getAllWarnings()
    {
        $warnings = IrcWarning::orderBy("created_at", "desc")->paginate(20);
        return view('admin.irc.warnings', compact('warnings'));
    }

    public function getCreateWarning(Request $request)
    {
        return view('admin.irc.warning-create');
    }

    public function createWarning(Request $request)
    {
        // Safety checks
        if ($request->username == null && $request->ident == null)
        {
            return redirect()->back()->withErrors(["Specify at least one value in the fields: user or ident"])->withInput();
        }

        if ($request->channel == null)
        {
            return redirect()->back()->withErrors(["Specify a channel this user will receive this message"])->withInput();
        }

        $this->ircWarningService->issueWarning(
            adminId: Auth::user()->id,
            username: $request->username,
            ident: $request->ident,
            warningMessage: $request->warning_message,
            channel: $request->channel
        );

        return redirect()->route('admin.irc')->with('status', 'Warning created');
    }
}
