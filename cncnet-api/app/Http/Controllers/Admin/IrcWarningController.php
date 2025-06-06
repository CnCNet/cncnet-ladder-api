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

class IrcWarningController extends Controller
{
    protected IrcWarningService $ircWarningService;

    public function __construct(IrcWarningService $ircWarningService)
    {
        $this->ircWarningService = $ircWarningService;
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

    public function getEditWarning(Request $request)
    {
        $warning = IrcWarning::findOrFail($request->id);
        return view('admin.irc.warning-edit', compact('warning'));
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

        return redirect()->route('admin.irc.warnings')->with('status', 'Warning created');
    }

    public function expireWarning(Request $request)
    {
        $this->ircWarningService->expireWarning($request->id);
        return redirect()->route('admin.irc.warnings')->with('status', 'Warning expired');
    }

    public function updateWarning(Request $request)
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

        $this->ircWarningService->updateWarning(
            warningId: $request->warning_id,
            adminId: Auth::user()->id,
            username: $request->username,
            ident: $request->ident,
            warningMessage: $request->warning_message,
            channel: $request->channel
        );

        return redirect()->route('admin.irc.warnings.edit', ['id' => $request->warning_id])->with('status', 'Warning updated');
    }
}
