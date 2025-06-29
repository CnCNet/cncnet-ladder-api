<?php

namespace App\Http\Controllers;

use App\Helpers\GameHelper;
use App\Http\Services\AdminService;
use App\Http\Services\LadderService;
use App\Models\Clan;
use App\Models\GameObjectSchema;
use App\Models\GameReport;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\LadderType;
use App\Models\Player;
use App\Models\PlayerCache;
use App\Models\PlayerGameReport;
use App\Models\SpawnOptionString;
use App\Models\URLHelper;
use App\Models\User;
use App\Models\Game;
use App\Models\UserPro;
use App\Models\UserSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\IpAddressHistory;
use App\Models\QmUserId;

class AdminController extends Controller
{
    private $ladderService;
    private $adminService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->adminService = new AdminService();
    }

    public function getAdminIndex(Request $request)
    {
        return view("admin.index", [
            "ladders" => $this->ladderService->getLadders(),
            "clan_ladders" => $this->ladderService->getLatestLadders(),
            "all_ladders" => \App\Models\Ladder::all(),
            "schemas" => \App\Models\GameObjectSchema::managedBy($request->user()),
            "user" => $request->user(),
        ]);
    }

    public function getWashedGames($ladderAbbreviation = null)
    {
        $ladder = \App\Models\Ladder::where('abbreviation', $ladderAbbreviation)->first();

        if ($ladder == null)
            abort(404);

        $ladderHistory = $ladder->currentHistory();

        $washedGames = \App\Models\GameAudit::where('game_audit.ladder_history_id', $ladderHistory->id)
            ->orderBy('game_audit.created_at', 'DESC')
            ->paginate(10);

        return view("admin.washed-games", [
            "washed_games" => $washedGames,
            "ladderHistory" => $ladderHistory
        ]);
    }

    public function getLadderSetupIndex(Request $request, $ladderId = null)
    {
        $ladder = \App\Models\Ladder::find($ladderId);

        if ($ladder === null)
            return null;

        $ladders = $this->ladderService->getLatestLadders();
        $clan_ladders = $this->ladderService->getLatestClanLadders();
        $objectSchemas = GameObjectSchema::all();
        $rule = $ladder->qmLadderRules;
        $mapPools = $ladder->mapPools;
        $maps = $ladder->maps;
        $user = $request->user();
        $spawnOptions = \App\Models\SpawnOption::all();
        $ladderTypes = \App\Models\Ladder::LADDER_TYPES;

        return view("admin.ladder-setup", compact(
            'ladders',
            'ladder',
            'clan_ladders',
            'objectSchemas',
            'rule',
            'mapPools',
            'maps',
            'user',
            'spawnOptions',
            'ladderTypes'
        ));
    }

    public function getGameSchemaSetup(Request $request, $gameSchemaId)
    {
        $gameSchema = GameObjectSchema::find($gameSchemaId);
        if ($gameSchema === null)
            abort(404);

        $countableGameObjects = $gameSchema->countableGameObjects;

        $heapNames = \App\Models\CountableObjectHeap::all();
        $managers = $gameSchema->managers;

        return view("admin.schema-setup", compact('gameSchema', 'countableGameObjects', 'heapNames', 'managers'));
    }

    public function saveSchemaManager(Request $request, $gameSchemaId)
    {
        $gameSchema = GameObjectSchema::find($gameSchemaId);
        if ($gameSchema === null)
            abort(404);

        $user = User::where('email', '=', $request->email)->first();

        if ($user === null)
        {
            $request->session()->flash('error', "No user with email: {$request->email}");
            return redirect()->back();
        }

        $manager = \App\Models\ObjectSchemaManager::firstOrCreate(['game_object_schema_id' => $gameSchema->id, 'user_id' => $user->id]);

        $request->session()->flash('success', "Manager added");
        return redirect()->back();
    }

    public function saveGameSchema(Request $request, $gameSchemaId)
    {
        $gameSchema = GameObjectSchema::find($gameSchemaId);
        if ($gameSchema === null)
            abort(404);

        $gameSchema->name = $request->name;
        $gameSchema->save();

        $request->session()->flash('success', "Schema has been updated");
        return redirect()->back();
    }


    public function saveGameObject(Request $request, $gameSchemaId, $objectId)
    {
        $gameSchema = GameObjectSchema::find($gameSchemaId);
        if ($gameSchema === null)
            abort(404);

        $object = \App\Models\CountableGameObject::find($objectId);

        if ($request->id == "new")
        {
            $object = new \App\Models\CountableObjectHeap;
        }
        else if ($object === null)
        {
            abort(404);
        }

        $request->session()->flash('success', "Object has been updated");
        $object->fill($request->all());
        $object->save();
        return redirect()->back();
    }

    public function postLadderSetupRules(Request $request, $ladderId)
    {
        return $this->adminService->saveQMLadderRulesRequest($request, $ladderId);
    }

    public function editSpawnOptionValue(Request $request)
    {
        if ($request->id == "new")
            $sov = new \App\Models\SpawnOptionValue;
        else
            $sov = \App\Models\SpawnOptionValue::find($request->id);

        if ($request->update !== null && $request->update == 2)
        {
            $sov->delete();
            $request->session()->flash('success', "Option has been deleted.");
        }
        else
        {
            if ($sov == null)
            {
                $sov = new \App\Models\SpawnOptionValue;
            }

            $sov->ladder_id = $request->ladder_id;
            $sov->qm_map_id = $request->qm_map_id;
            $sov->spawn_option_id = $request->spawn_option_id;
            $sov->value_id = SpawnOptionString::findOrCreate($request->value)->id;
            $sov->save();
            $request->session()->flash('success', "Option has been updated.");
        }

        $request->session()->flash('success', "No action applied");
        return redirect()->back();
    }

    public function getChatBannedUsers(Request $request)
    {
        if ($request->user() == null || !$request->user()->isAdmin())
            return response('Unauthorized.', 401);

        $users = User::where("chat_allowed", false)->get();

        return view("admin.chatban-users", [
            "users" => $users,
        ]);
    }

    public function getManageUsersIndex(Request $request)
    {
        $hostname = $request->hostname;
        $userIdOrAlias = $request->userIdOrAlias ?: $request->input('userId');
        $search = $request->search;

        if (!$request->user() || !$request->user()->isAdmin())
        {
            return response('Unauthorized.', 401);
        }

        $users = collect();

        if ($search)
        {
            $players = Cache::remember(
                "admin/users/players/{$search}",
                20 * 60,
                fn() =>
                Player::where('username', '=', $search)->get()
            );

            $playerUsers = $players->map(fn($p) => $p->user)->filter();
            $users = $users->concat($playerUsers);
        }

        if ($userIdOrAlias)
        {
            $queryUsers = Cache::remember("admin/users/users/{$userIdOrAlias}", 20 * 60, function () use ($userIdOrAlias)
            {
                return User::where(function ($query) use ($userIdOrAlias)
                {
                    if (is_numeric($userIdOrAlias))
                    {
                        $query->orWhere('id', $userIdOrAlias);
                    }
                    $query->orWhere('alias', 'like', '%' . $userIdOrAlias . '%');
                })->get();
            });

            $users = $users->concat($queryUsers);
        }

        // Remove nulls and duplicates
        $users = $users->filter()->unique('id')->take(10)->values(); // limit to 10 users maximum

        $userIds = $users->pluck('id');
        $ipAddressIds = $users->pluck('ip_address_id')->filter()->unique();

        $now = Carbon::now();
        $start = $now->startOfMonth()->toDateTimeString();
        $end = $now->endOfMonth()->toDateTimeString();

        $playerNicknames = Player::with('ladder')
            ->whereIn('user_id', $userIds)
            ->get()
            ->groupBy('user_id');

        $ladderHistory = LadderHistory::where('starts', $start)
            ->where('ends', $end)
            ->first();

        $ipHistories = IpAddressHistory::with('ipaddress')
            ->whereIn('user_id', $userIds)
            ->orderBy('created_at', 'desc')  // Most recent first
            ->get()
            ->groupBy('user_id');

        $ipDuplicates = IpAddressHistory::whereIn('ip_address_id', $ipAddressIds)
            ->whereNotIn('user_id', $userIds) // avoid self
            ->with('user')
            ->get()
            ->groupBy('ip_address_id');

        $qmUserIds = QmUserId::with('user')
            ->whereIn('user_id', $userIds)
            ->get()
            ->groupBy('user_id');

        // Map ip_address_id → user_id for reverse lookup
        $userIpMap = $users->mapWithKeys(fn($u) => [$u->ip_address_id => $u->id]);

        // Rebuild ipDuplicates structure to group by user_id
        $ipDuplicatesByUser = [];
        foreach ($ipDuplicates as $ipId => $dupeRecords)
        {
            $uid = $userIpMap[$ipId] ?? null;
            if ($uid !== null)
            {
                $ipDuplicatesByUser[$uid] = $dupeRecords->pluck('user')->filter()->unique('id')->values();
            }
        }

        return view("admin.manage-users", [
            "users" => $users,
            "search" => $search,
            "userId" => null,
            "hostname" => $hostname,
            "alias" => null,
            "userIdOrAlias" => $userIdOrAlias,
            "playerNicknames" => $playerNicknames,
            "ladderHistory" => $ladderHistory,
            "ipHistories" => $ipHistories,
            "ipDuplicates" => $ipDuplicatesByUser,
            "qmUserIds" => $qmUserIds,
        ]);
    }


    public function saveProList(Request $request)
    {
        try
        {
            $ladder = Ladder::findOrFail($request->ladderId);
            UserPro::where("ladder_id", $ladder->id)->delete();

            if ($request->userProIds)
            {
                foreach ($request->userProIds as $userId)
                {
                    $userPro = new UserPro();
                    $userPro->user_id = $userId;
                    $userPro->ladder_id = $ladder->id;
                    $userPro->save();
                }
            }

            $request->session()->flash('success', "List saved");
            return redirect()->back();
        }
        catch (Exception $ex)
        {
            report($ex);
        }
    }

    public function getManageClansIndex(Request $request)
    {
        if ($request->user() == null || !$request->user()->isAdmin())
            return response('Unauthorized.', 401);

        $ladder = Ladder::where("abbreviation", "ra2-cl")->first();
        $ladderHistory = $ladder->currentHistory();

        if ($request->search)
        {
            $clans = Clan::where("short", "like", "%" . $request->search . "%")->paginate(50);
        }
        else
        {
            $clans = Clan::orderBy("short")->paginate(50);
        }

        return view("admin.manage-clans", [
            "clans" => $clans,
            "ladder" => $ladder,
            "history" => $ladderHistory,
            "search" => $request->search
        ]);
    }

    public function updateClan(Request $request)
    {
        if ($request->user() == null || !$request->user()->isAdmin())
            return response('Unauthorized.', 401);

        $clan = Clan::find($request->clan_id);
        $clan->fill($request->all());
        $clan->save();

        $request->session()->flash("success", 'Clan updated');
        return redirect()->back();
    }

    public function getEditUser(Request $request)
    {
        $user = User::where("id", $request->userId)->first();

        return view("admin.edit-user", [
            "user" => $user
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = User::where("id", $request->userId)->first();

        if ($request->removeUserAvatar == "on")
        {
            $user->removeAvatar();
        }

        if ($request->removeUserEmoji == "on")
        {
            $user->emoji = null;
            $user->save();
        }

        if ($request->restrictAvatarUpload == "on")
        {
            $user->restrictAvatarUpload(true);
        }
        else
        {
            $user->restrictAvatarUpload(false);
        }

        if ($request->userAllowedToChat == "on")
        {
            $user->chat_allowed = true;
            $user->save();
        }
        else
        {
            $user->chat_allowed = false;
            $user->save();
        }

        if ($request->can_observe == "on" && !$user->isModerator())
        {
            $user->group = 'Observer';
            $user->save();
        }
        else if ($request->can_observe != "on" && !$user->isModerator())
        {
            $user->group = 'User';
            $user->save();
        }

        if ($request->exists('alias'))
        {
            $request->validate(
                [
                    'alias' => [
                        'nullable',
                        'string',
                        'min:2',
                        'max:20',
                        'regex:/^[A-Z][a-zA-Z]{1,19}$/',
                        'unique:users,alias,' . $user->id,
                    ]
                ],
                [
                    'alias.regex' => 'The alias must start with an uppercase letter and contain only letters.',
                    'alias.unique' => 'This alias is already taken.',
                    'alias.min' => 'Minimum length for alias is 2.',
                    'alias.max' => 'Maximum length for alias is 20.'
                ]
            );

            if (preg_match('/[A-Z]{2}/', $request->alias))
            {
                return back()->withErrors(['alias' => 'No consecutive uppercase letters allowed in alias.'])->withInput();
            }

            $user->updateAlias($request->alias);
        }

        $user->userSettings->is_anonymous = $request->is_anonymous == "on" ? true : false;
        $user->userSettings->allow_2v2_ladders = $request->allow_2v2_ladders == "on" ? true : false;
        $user->userSettings->save();

        return view("admin.edit-user", [
            "user" => $user
        ]);
    }

    public function getManageGameIndex(Request $request, $cncnetGame = null)
    {
        $ladder = \App\Models\Ladder::where("abbreviation", "=", $cncnetGame)->first();

        if ($ladder == null)
            return "No ladder";

        $date = Carbon::now();
        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $history = \App\Models\LadderHistory::where("starts", "=", $start)
            ->where("ends", "=", $end)
            ->where("ladder_id", "=", $ladder->id)
            ->first();

        $games = Game::where("ladder_history_id", "=", $history->id)->orderBy("id", "DESC")->limit(100);
        return view("admin.manage-games", ["games" => $games, "ladder" => $ladder, "history" => $history]);
    }

    public function deleteGame(Request $request)
    {
        $game = Game::find($request->game_id);
        if ($game == null) return "Game not found";

        // Just remove the game_report_id linkage rather than actually delete anything
        $game->game_report_id = null;
        $game->save;

        $request->session()->flash('success', "Game has been deleted");
        return redirect()->back();
    }

    public function switchGameReport(Request $request)
    {
        $game = \App\Models\Game::find($request->game_id);
        if ($game === null) return "Game not found";

        $gameReport = $game->allReports()->find($request->game_report_id);
        if ($gameReport === null) return "Game Report not found";

        $currentReport = $game->report()->first();
        if ($currentReport !== null)
        {
            $currentReport->best_report = false;
            $currentReport->save();
        }

        $game->game_report_id = $request->game_report_id;
        $game->save();

        $gameReport->best_report = true;
        $gameReport->save();

        $this->ladderService->undoCache($currentReport);
        $this->ladderService->updateCache($gameReport);
        return redirect()->back();
    }

    public function washGame(Request $request)
    {
        $this->adminService->doWashGame($request->game_id, $request->user()->name);
        return redirect()->back();
    }

    public function remSide(Request $request, $ladderId = null)
    {
        $ladder = \App\Models\Ladder::find($ladderId);

        if ($ladder === null || $ladderId === null)
        {
            $request->session()->flash('error', "Ladder not found");
            return redirect()->back();
        }

        $side = $ladder->sides()->where('local_id', '=', $request->local_id)->first();
        if ($side === null)
        {
            $request->session()->flash('error', 'Side not found');
            return redirect()->back();
        }
        $side->delete();

        $request->session()->flash('success', 'Side deleted');
        return redirect()->back();
    }

    public function addSide(Request $request, $ladderId = null)
    {
        $ladder = \App\Models\Ladder::find($ladderId);

        if ($ladder === null || $ladderId === null)
        {
            $request->session()->flash('error', "Ladder not found");
            return redirect()->back();
        }

        $side = $ladder->sides()->where('local_id', '=', $request->local_id)->first();
        if ($side === null)
        {
            $side = new \App\Models\Side;
            $side->ladder_id = $ladder->id;
            $side->local_id = $request->local_id;
        }
        $side->name = $request->name;
        $side->save();
        $request->session()->flash('success', "Side has been added or updated");

        return redirect()->back();
    }


    public function addAdmin(Request $request, $ladderId = null)
    {
        if ($ladderId === null)
        {
            $request->session()->flash('error', "No ladder specified");
        }

        $user = \App\Models\User::where('email', '=', $request->email)->first();
        if ($request->email === null || $user == null)
        {
            $request->session()->flash('error', "Unable to add the user as admin");
            return redirect()->back();
        }

        $ladderAdmin = \App\Models\LadderAdmin::findOrCreate($user->id, $ladderId);

        $ladderAdmin->admin = true;
        $ladderAdmin->moderator = true;
        $ladderAdmin->tester = true;
        $ladderAdmin->save();

        $request->session()->flash('success', "The user {$user->email} has been made promoted to admin");
        return redirect()->back();
    }

    public function removeAdmin(Request $request, $ladderId = null)
    {
        if ($ladderId === null)
        {
            $request->session()->flash('error', "No ladder specified");
        }

        if ($request->ladder_admin_id === null)
        {
            $request->session()->flash('error', "Unable to remove the user");
            return redirect()->back();
        }

        $ladderAdmin = \App\Models\LadderAdmin::find($request->ladder_admin_id);
        if ($ladderAdmin === null)
        {
            $request->session()->flash('error', "Unable to find the requested admin");
            return redirect()->back();
        }

        $ladderAdmin->admin = false;
        $ladderAdmin->moderator = false;
        $ladderAdmin->tester = false;
        $ladderAdmin->save();

        $request->session()->flash('success', "The admin {$ladderAdmin->user->email} has been removed");
        return redirect()->back();
    }

    public function addModerator(Request $request, $ladderId = null)
    {
        if ($ladderId === null)
        {
            $request->session()->flash('error', "No ladder specified");
        }

        $user = \App\Models\User::where('email', '=', $request->email)->first();
        if ($request->email === null || $user == null)
        {
            $request->session()->flash('error', "Unable to add the user as moderator");
            return redirect()->back();
        }

        $ladderAdmin = \App\Models\LadderAdmin::findOrCreate($user->id, $ladderId);

        $ladderAdmin->admin = false;
        $ladderAdmin->moderator = true;
        $ladderAdmin->tester = true;
        $ladderAdmin->save();

        $request->session()->flash('success', "The user {$user->email} has been made promoted to moderator");
        return redirect()->back();
    }

    public function removeModerator(Request $request, $ladderId = null)
    {
        if ($ladderId === null)
        {
            $request->session()->flash('error', "No ladder specified");
        }

        if ($request->ladder_admin_id === null)
        {
            $request->session()->flash('error', "Unable to remove the user");
            return redirect()->back();
        }

        $ladderAdmin = \App\Models\LadderAdmin::find($request->ladder_admin_id);
        if ($ladderAdmin === null)
        {
            $request->session()->flash('error', "Unable to find the requested moderator");
            return redirect()->back();
        }

        $ladderAdmin->admin = false;
        $ladderAdmin->moderator = false;
        $ladderAdmin->tester = false;
        $ladderAdmin->save();

        $request->session()->flash('success', "The moderator {$ladderAdmin->user->email} has been removed");
        return redirect()->back();
    }

    public function addTester(Request $request, $ladderId = null)
    {
        if ($ladderId === null)
        {
            $request->session()->flash('error', "No ladder specified");
        }

        $user = \App\Models\User::where('email', '=', $request->email)->first();
        if ($request->email === null || $user == null)
        {
            $request->session()->flash('error', "Unable to add the user as tester");
            return redirect()->back();
        }

        $ladderAdmin = \App\Models\LadderAdmin::findOrCreate($user->id, $ladderId);

        $ladderAdmin->admin = false;
        $ladderAdmin->moderator = false;
        $ladderAdmin->tester = true;
        $ladderAdmin->save();

        $request->session()->flash('success', "The user {$user->email} has been made promoted to tester");
        return redirect()->back();
    }

    public function removeTester(Request $request, $ladderId = null)
    {
        if ($ladderId === null)
        {
            $request->session()->flash('error', "No ladder specified");
        }

        if ($request->ladder_admin_id === null)
        {
            $request->session()->flash('error', "Unable to remove the user");
            return redirect()->back();
        }

        $ladderAdmin = \App\Models\LadderAdmin::find($request->ladder_admin_id);
        if ($ladderAdmin === null)
        {
            $request->session()->flash('error', "Unable to find the requested tester");
            return redirect()->back();
        }

        $ladderAdmin->admin = false;
        $ladderAdmin->moderator = false;
        $ladderAdmin->tester = false;
        $ladderAdmin->save();

        $request->session()->flash('success', "The tester {$ladderAdmin->user->email} has been removed");
        return redirect()->back();
    }

    public function getLadderPlayer(Request $request, $ladderId = null, $playerId = null)
    {
        $mod = $request->user();
        if ($playerId === null)
            return;

        $player = Player::find($playerId);

        if ($player === null || !$mod->isLadderMod($player->ladder))
            return;

        $user = $player->user;

        $ladderService = new LadderService;
        $history = $ladderService->getActiveLadderByDate(Carbon::now()->format('m-Y'), $player->ladder->abbreviation);

        $bans = $user->bans()->orderBy('created_at', 'DESC')->get();

        return view(
            "admin.moderate-player",
            [
                "mod"    => $mod,
                "player" => $player,
                "user"   => $user,
                "bans"   => $bans,
                "ladder" => $player->ladder,
                "history" => $history
            ]
        );
    }

    public function getUserBan(Request $request, $ladderId = null, $playerId = null, $banType = 0)
    {
        $mod = $request->user();
        if ($playerId === null)
            return;

        $player = \App\Models\Player::find($playerId);

        if ($player === null || !$mod->isLadderMod($player->ladder))
            return;

        $user = $player->user;

        return view(
            "admin.edit-ban",
            [
                "mod"    => $mod,
                "player" => $player,
                "user"   => $user,
                "ladder" => $player->ladder,
                "id" => null,
                "expires" => null,
                "admin_id" => $mod->id,
                "user_id" => $user->id,
                "start_ban" => true, // new ban,
                "end_ban" => false,
                "ban_type" => $banType,
                "internal_note" => "",
                "plubic_reason" => "",
                "ip_address_id" => $user->ip->id,
                "banDesc" => \App\Models\Ban::typeToDescription($banType) . " - " . \App\Models\Ban::banStyle($banType)
            ]
        );
    }

    public function editUserBan(Request $request, $ladderId = null, $playerId = null, $banId = null)
    {
        $mod = $request->user();
        if ($playerId === null)
            return;

        $player = \App\Models\Player::find($playerId);

        if ($player === null || !$mod->isLadderMod($player->ladder))
            return;

        $ban = \App\Models\Ban::find($banId);
        if ($player === null)
            return;

        $user = $player->user;

        $expires = $ban->expires->eq(\App\Models\Ban::unstartedBanTime()) ? null : $ban->expires;

        return view(
            "admin.edit-ban",
            [
                "mod"    => $mod,
                "player" => $player,
                "user"   => $user,
                "ladder" => $player->ladder,
                "id" => $ban->id,
                "expires" => $expires,
                "admin_id" => $mod->id,
                "user_id" => $user->id,
                "ban_type" => $ban->ban_type,
                "internal_note" => $ban->internal_note,
                "plubic_reason" => $ban->plubic_reason,
                "ip_address_id" => $ban->ip_address_id,
                "start_ban" => $request->start_ban,
                "banDesc" => \App\Models\Ban::typeToDescription($ban->ban_type) . " - " . \App\Models\Ban::banStyle($ban->ban_type)
            ]
        );
    }

    public function saveUserBan(Request $request, $ladderId = null, $playerId = null, $banId = null)
    {
        $mod = $request->user();

        if ($playerId === null)
            return;

        $player = \App\Models\Player::find($playerId);

        if ($player === null || !$mod->isLadderMod($player->ladder))
            return;

        $ban = \App\Models\Ban::find($banId);
        if ($ban === null)
        {
            $ban = new \App\Models\Ban;
        }

        foreach ($ban->fillable as $col)
        {
            if ($request->has($col))
            {
                $ban[$col] = $request[$col];
            }
        }

        $ban->save();

        $banFlash = \App\Models\Ban::banStyle($request->ban_type);

        if ($request->end_ban && $request->boolean('end_ban') === true) // end the ban
        {
            $ban->expires = Carbon::now();
            $banFlash = "has ended.";
        }
        else if ($request->start_ban && $request->boolean('start_ban') === true)
        {
            $ban->checkStartBan(true); // start new ban
        }
        else
        {
            $banFlash = "reason updated"; // ban wasn't started or ended, the reason got updated
        }

        $ban->save();

        $request->session()->flash('success', "Ban " . $banFlash);
        return redirect()->action([AdminController::class, 'getLadderPlayer'], ['ladderId' => $ladderId, 'playerId' => $playerId]);
    }

    public function editLadderAlert(Request $request, $ladderId)
    {
        $this->validate($request, [
            'message'   => 'string|required',
            'expires_at' => 'required'
        ]);

        $ladder = \App\Models\Ladder::find($ladderId);

        $alert = \App\Models\LadderAlert::find($request->id);

        if ($request->submit == "delete")
        {
            $alert->delete();
            $request->session()->flash('success', "Alert has been deleted");
            return redirect()->back();
        }

        if ($request->id == 'new' || $request->id === null)
        {
            $alert = new \App\Models\LadderAlert;
            $alert->ladder_id = $ladder->id;
        }

        if ($alert === null)
        {
            $request->session()->flash('error', "No alert found with id " . $request->id);
            return redirect()->back();
        }

        $alert->message = $request->message;
        $alert->expires_at = $request->expires_at;
        $alert->save();

        $request->session()->flash('success', "Alert has been updated");
        return redirect()->back();
    }

    public function editPlayerAlert(Request $request, $ladderId, $playerId)
    {
        $player = \App\Models\Player::find($playerId);

        $alert = \App\Models\PlayerAlert::find($request->id);
        if ($request->submit == "delete")
        {
            $alert->delete();
            $request->session()->flash('success', "Alert has been deleted");
            return redirect()->back();
        }

        if ($request->id == 'new' || $request->id === null)
        {
            $alert = new \App\Models\PlayerAlert;
            $alert->player_id = $player->id;
        }

        $alert->message = $request->message;
        $alert->expires_at = $request->expires_at;
        $alert->save();

        $request->session()->flash('success', "Alert has been updated");
        return redirect()->back();
    }

    /**
     * Set all player's points for their played games to 0 of a given ladder history.
     */
    public function laundryService(Request $request)
    {
        $ladderHistoryId = $request->ladderHistory_id;

        $playerId = $request->player_id;

        $ladderHistory = \App\Models\LadderHistory::find($ladderHistoryId);

        if ($ladderHistory == null)
            $request->session()->flash('error', 'Unabled to find ladder history');

        $playerCache = \App\Models\PlayerCache::where('player_id', '=', $playerId)
            ->where('ladder_history_id', '=', $ladderHistoryId)
            ->first();
        if ($playerCache == null)
            $request->session()->flash('error', 'Unabled to find player cache');

        //Query for the player's game reports from the ladder history month
        $playerGameReports = \App\Models\PlayerGameReport::where('player_id', '=', $playerId)->where('created_at', '<', $ladderHistory->ends)->where('created_at', '>', $ladderHistory->starts)->get();

        //set the players points to 0
        foreach ($playerGameReports as $playerGameReport)
        {
            if ($playerGameReport->points != 0)
            {
                $playerGameReport->backupPts = $playerGameReport->points;
                $playerGameReport->points = 0;
                $playerGameReport->save();
            }
        }

        $playerCache->points = 0;
        $playerCache->save();

        $request->session()->flash('success', "Player games have been laundered");
        return redirect()->back();
    }

    /**
     * Reverse Launder and restore a player's points from their games.
     */
    public function undoLaundryService(Request $request)
    {
        $ladderHistoryId = $request->ladderHistory_id;

        $playerId = $request->player_id;

        $ladderHistory = \App\Models\LadderHistory::find($ladderHistoryId);

        if ($ladderHistory == null)
            $request->session()->flash('error', 'Unabled to find ladder history');

        $playerCache = \App\Models\PlayerCache::where('player_id', '=', $playerId)
            ->where('ladder_history_id', '=', $ladderHistoryId)
            ->first();

        if ($playerCache == null)
            $request->session()->flash('error', 'Unabled to find player cache');

        $player = \App\Models\Player::find($playerId);
        if ($player == null)
            $request->session()->flash('error', 'Unabled to find player');

        //Query for the player's game reports from the ladder history month
        $playerGameReports = \App\Models\PlayerGameReport::where('player_id', '=', $playerId)
            ->where('created_at', '<', $ladderHistory->ends)
            ->where('created_at', '>', $ladderHistory->starts)->get();

        //reset player's points from games played using backupPts
        foreach ($playerGameReports as $playerGameReport)
        {
            if ($playerGameReport->backupPts != 0)
            {
                $playerGameReport->points = $playerGameReport->backupPts;
            }
            $playerGameReport->backupPts = 0;
            $playerGameReport->save();
        }

        $playerCache->points = $player->points($ladderHistory);
        $playerCache->save();

        $request->session()->flash('success', "Player games have been reset");
        return redirect()->back();
    }

    public function getPlayerRatings(Request $request, $ladderAbbreviation = null)
    {
        if ($ladderAbbreviation == null)
        {
            $ladderAbbreviation = GameHelper::$GAME_BLITZ;
        }

        $ladder = Ladder::where("abbreviation", $ladderAbbreviation)->first();

        if ($request->search)
        {
            $byPlayer = Player::where("username", "like", "%" . $request->search . "%")
                ->where("ladder_id", $ladder->id)
                ->get();

            if ($byPlayer == null)
            {
                $request->session()->flash('error', "Player " . $request->search . " not found");
                return redirect()->back();
            }

            $users = User::join("user_ratings as ur", "ur.user_id", "=", "users.id")
                ->orderBy("ur.rating", "DESC")
                ->whereIn("users.id", $byPlayer->pluck("user_id"))
                ->select(["users.*", "ur.rating", "ur.rated_games", "ur.peak_rating"])
                ->paginate(50);
        }
        else
        {
            $users = User::join("user_ratings as ur", "ur.user_id", "=", "users.id")
                ->orderBy("ur.rating", "DESC")
                ->select(["users.*", "ur.rating", "ur.rated_games", "ur.peak_rating"])
                ->paginate(50);
        }

        $history = $ladder->currentHistory();
        $ladders = Ladder::all();

        return view("admin.players.ratings", [
            "ladder" => $ladder,
            "users" => $users,
            "ladders" => $ladders,
            "history" => $history,
            "abbreviation" => $ladderAbbreviation,
            "search" => $request->search
        ]);
    }

    public function updateUserLadderTier(Request $request)
    {
        $ladder = Ladder::where("id", $request->ladder_id)->first();
        if ($ladder == null)
        {
            $request->session()->flash('error', "Ladder not found");
            return redirect()->back();
        }

        $user = User::find($request->user_id);
        if ($user == null)
        {
            $request->session()->flash('error', "User not found");
            return redirect()->back();
        }

        $userTier = $user->getUserLadderTier($ladder);
        if ($request->tier == 1 || $request->tier == 2)
        {
            $userTier->tier = $request->tier;
        }

        $userCanPlayBothTiers = $request->canPlayBothTiers;
        if ($userCanPlayBothTiers == "on")
        {
            $userTier->both_tiers = true;
        }
        else
        {
            $userTier->both_tiers = false;
        }
        $userTier->save();

        // Trigger cache update
        $history = $ladder->currentHistory();
        if ($history)
        {
            $updates = \App\Models\PlayerCache::where("ladder_history_id", '=', $history->id)->get();
            foreach ($updates as $update)
            {
                $update->mark();
            }
        }

        $request->session()->flash('success', "User Tier updated");
        return redirect()->back();
    }

    public function editPlayerName(Request $request, $ladderId, $playerId)
    {
        try
        {
            $this->validate($request, [
                'player_name' => 'required|string|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/|max:11', //\x7c = | aka pipe
            ]);
        }
        catch (ValidationException $e)
        {
            Log::error('Validation failed', [
                'url' => $request->fullUrl(),
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            throw $e;
        }

        $history = LadderHistory::where('id', $request->history_id)->first();

        $player = Player::where('id', $playerId)
            ->where('ladder_id', $ladderId)
            ->first();
        $oldName = $player->username;

        if ($player == null)
        {
            Log::error("No existing player found with this player id: $player->id");
            $request->session()->flash('error', "No existing player found with this player id");
            return redirect()->back();
        }

        $playerCaches = PlayerCache::where('player_id', $player->id)->get();

        if ($playerCaches == null || $playerCaches->count() == 0)
        {
            Log::error("No player caches found with this player id: $player->id");
            $request->session()->flash('error', "Error updating player");
            return redirect()->back();
        }

        $newName = $request->player_name;
        $newName = trim($newName);

        //check if this name already belongs to another player in this ladder
        $existing_players_count = Player::where('username', $newName)
            ->where('ladder_id', $history->ladder->id)
            ->count();
        if ($existing_players_count > 0)
        {
            Log::error("Username $newName is already taken for this ladder.");
            $request->session()->flash('error', "Username $newName is already taken for this ladder.");
            return redirect()->back();
        }

        //update player name to the new name
        $player->username = $newName;
        $player->save();

        //update player name in player caches
        foreach ($playerCaches as $playerCache)
        {
            $playerCache->player_name = $player->username;
            $playerCache->save();
        }

        $url = URLHelper::getPlayerProfileUrl($history, $player->username);
        $request->session()->flash('success', "Player name has been updated to " . $player->username);
        Log::info("Successfully updated player '$oldName' to '$newName'");
        return redirect()->to($url);
    }

    /**
     * TODO this method currently unused, create a possible view to see this data
     */
    public function fetchBails(Request $request)
    {
        $ladder = \App\Models\Ladder::where('abbreviation', $request->abbreviation)->first();

        if ($ladder == null)
            abort(404);

        $ladderHistory = $ladder->currentHistory();

        $bailedGames = $this->adminService->fetchBailedGames($ladderHistory)->get();
    }


    /**
     * For points for games, where both player got zero points or both players gained points.
     */
    public function fixPoints(Request $request)
    {
        $inputs = $request->validate([
            'game_id' => 'required|exists:games,id',
            'game_report_id' => 'required|exists:game_reports,id',
            'mode' => 'required|in:zero_for_loser,fix_points',
            'player_points' => 'required_if:mode,fix_points|array'
        ]);


        $report = GameReport::with('playerGameReports')->findOrFail($inputs['game_report_id']);

        if ($report->playerGameReports->count() !== 2)
        {
            return back()->with('error', 'Cannot fix games with more or less than 2 players.');
        }

        foreach ($report->playerGameReports as $pgr)
        {
            if ($inputs['mode'] === 'fix_points')
            {
                $submittedPoints = $inputs['player_points'];
                $playerId = $pgr->player_id;
                if (!isset($submittedPoints[$playerId]))
                {
                    return back()->with('error', 'No points submitted for ' . $playerId . '.');
                }
                $pgr->points = (int)$submittedPoints[$playerId];
            }
            elseif ($inputs['mode'] === 'zero_for_loser')
            {
                if (!$pgr->won && $pgr->points > 0)
                {
                    $pgr->points = 0;
                }
            }
            $pgr->save();
        }

        Log::info('Fixed points: ', [
            'game_id' => $inputs['game_id'],
            'report_id' => $report->id,
            'by_admin' => auth()->id(),
        ]);

        return back()->with('success', 'Points fixed.');
    }

    /**
     * Simplified points calculation for ladder games. Only used to fix broken game results. 
     */
    public function awardedPointsPreview(GameReport $gameReport, LadderHistory $history): array
    {
        $playerGameReports = $gameReport->playerGameReports()->with('player')->get();

        if ($playerGameReports->count() !== 2)
        {
            return [];
        }

        $winner = $playerGameReports->firstWhere(fn($pgr) => $pgr->wonOrDisco());
        $loser = $playerGameReports->firstWhere(fn($pgr) => !$pgr->wonOrDisco());

        if (!$winner || !$loser)
        {
            return [];
        }

        $winnerPointsBefore = $winner->player->pointsBefore($history, $winner->game_id);
        $loserPointsBefore = $loser->player->pointsBefore($history, $loser->game_id);
        $diff = $loserPointsBefore - $winnerPointsBefore;

        $we = 1 / (pow(10, abs($diff) / 600) + 1);
        if ($diff > 0)
        {
            $we = 1 - $we;
        }

        $wol_k = $history->ladder->qmLadderRules->wol_k;
        $wol = (int)($wol_k * $we);
        $gvc = 8;

        $winnerPoints = $gvc + $wol;
        if ($winnerPointsBefore < 10 * ($gvc + $wol))
        {
            $loserPoints = -1 * (int)($loserPointsBefore / 10);
        }
        else
        {
            $loserPoints = -1 * ($gvc + $wol);
        }

        $loserCache = $loser->player->playerCache($history->id);
        if ($loserPoints < 0 && (!$loserCache || $loserCache->points < 0))
        {
            $loserPoints = 0;
        }

        return [
            [
                'player_id' => $winner->player->id,
                'player' => $winner->player->username,
                'calculated_points' => $winnerPoints,
                'won' => true,
            ],
            [
                'player_id' => $loser->player->id,
                'player' => $loser->player->username,
                'calculated_points' => $loserPoints,
                'won' => false,
            ]
        ];
    }
}


function ini_to_b($string)
{
    if ($string == "Null") return null;
    if ($string == "Random") return -1;
    return $string == "Yes" ? true : false;
}
