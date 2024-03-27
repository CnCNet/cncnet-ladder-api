<?php

namespace App\Http\Controllers;

use App\Helpers\GameHelper;
use App\Http\Services\AdminService;
use App\Http\Services\LadderService;
use App\Models\Clan;
use App\Models\GameObjectSchema;
use App\Models\Ladder;
use App\Models\Player;
use App\Models\SpawnOptionString;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

    public function getCanceledMatches($ladderAbbreviation = null)
    {
        $ladder = \App\Models\Ladder::where('abbreviation', $ladderAbbreviation)->first();

        if ($ladder == null)
            abort(404);

        $matches = \App\Models\QmCanceledMatch::where('qm_canceled_matches.ladder_id', $ladder->id)
            ->join('players as p', 'qm_canceled_matches.player_id', '=', 'p.id')
            ->orderBy('qm_canceled_matches.created_at', 'DESC')
            ->select("qm_canceled_matches.*", "p.username")
            ->paginate(50);

        return view("admin.canceled-matches", [
            "canceled_matches" => $matches,
            "ladder" => $ladder
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

        return view("admin.ladder-setup", compact(
            'ladders',
            'ladder',
            'clan_ladders',
            'objectSchemas',
            'rule',
            'mapPools',
            'maps',
            'user',
            'spawnOptions'
        ));
    }

    public function getGameSchemaSetup(Request $request, $gameSchemaId)
    {
        $gameSchema = \App\Models\GameObjectSchema::find($gameSchemaId);
        if ($gameSchema === null)
            abort(404);

        $countableGameObjects = $gameSchema->countableGameObjects;

        $heapNames = \App\Models\CountableObjectHeap::all();
        $managers = $gameSchema->managers;

        return view("admin.schema-setup", compact('gameSchema', 'countableGameObjects', 'heapNames', 'managers'));
    }

    public function saveSchemaManager(Request $request, $gameSchemaId)
    {
        $gameSchema = \App\Models\GameObjectSchema::find($gameSchemaId);
        if ($gameSchema === null)
            abort(404);

        $user = \App\Models\User::where('email', '=', $request->email)->first();

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
        $gameSchema = \App\Models\GameObjectSchema::find($gameSchemaId);
        if ($gameSchema === null)
            abort(404);

        $gameSchema->name = $request->name;
        $gameSchema->save();

        $request->session()->flash('success', "Schema has been updated");
        return redirect()->back();
    }


    public function saveGameObject(Request $request, $gameSchemaId, $objectId)
    {
        $gameSchema = \App\Models\GameObjectSchema::find($gameSchemaId);
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
        $userId = $request->userId;
        $search = $request->search;
        $players = null;
        $users = null;

        if ($request->user() == null || !$request->user()->isAdmin())
            return response('Unauthorized.', 401);

        if ($search)
        {
            $players = Cache::remember("admin/users/{$search}", 20, function () use ($search)
            {
                return \App\Models\Player::where('username', '=', $search)->get();
            });
        }
        else if ($userId)
        {
            $users = Cache::remember("admin/users/users/{$userId}", 20, function () use ($userId)
            {
                return \App\Models\User::where("id", $userId)->get();
            });
        }

        return view("admin.manage-users", [
            "users" => $users != null ? $users : [],
            "players" => $players != null ? $players : [],
            "search" => $search,
            "userId" => $userId,
            "hostname" => $hostname
        ]);
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

        $user->updateAlias($request->alias);

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

        $games = \App\Models\Game::where("ladder_history_id", "=", $history->id)->orderBy("id", "DESC")->limit(100);
        return view("admin.manage-games", ["games" => $games, "ladder" => $ladder, "history" => $history]);
    }

    public function deleteGame(Request $request)
    {
        $game = \App\Models\Game::find($request->game_id);
        if ($game == null) return "Game not found";

        // Just remove the game_report_id linkage rather than actually delete anything
        $game->game_report_id = null;
        $game->save;

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

        $player = \App\Models\Player::find($playerId);

        if ($player === null || !$mod->isLadderMod($player->ladder))
            return;

        $user = $player->user;

        $ladderService = new LadderService;
        $history = $ladderService->getActiveLadderByDate(Carbon::now()->format('m-Y'), $player->ladder->abbreviation);

        return view(
            "admin.moderate-player",
            [
                "mod"    => $mod,
                "player" => $player,
                "user"   => $user,
                "bans"   => $user->bans()->orderBy('created_at', 'DESC')->get(),
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
                "ban_type" => $banType,
                "internal_note" => "",
                "plubic_reason" => "",
                "ip_address_id" => $user->ip->id,
                "start_or_end" => false,
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

        return view(
            "admin.edit-ban",
            [
                "mod"    => $mod,
                "player" => $player,
                "user"   => $user,
                "ladder" => $player->ladder,
                "id" => $ban->id,
                "expires" => $ban->expires->eq(\App\Models\Ban::unstartedBanTime()) ? null : $ban->expires,
                "admin_id" => $mod->id,
                "user_id" => $user->id,
                "ban_type" => $ban->ban_type,
                "internal_note" => $ban->internal_note,
                "plubic_reason" => $ban->plubic_reason,
                "ip_address_id" => $ban->ip_address_id,
                "start_or_end" => false,
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

        if ($player === null)
            return;

        $user = $player->user;

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

        if (!$request->start_or_end && $ban->ban_type == \App\Models\Ban::BAN_SHADOW)
        {
            // Start ban straight away
            $ban->checkStartBan(true);
        }

        $banFlash = \App\Models\Ban::banStyle($request->ban_type);

        if ($request->start_or_end)
        {
            if ($ban->expires !== null && $ban->expires->gt(Carbon::now()))
            {
                $ban->expires = Carbon::now();
                $banFlash = "has ended.";
            }
            else if ($ban->expires === null || $ban->expires->eq(\App\Models\Ban::unstartedBanTime()))
            {
                $ban->checkStartBan(true);
                $banFlash = "has started.";
            }
            else
            {
                $ban->checkStartBan(false);
            }
        }
        else
            $ban->checkStartBan(false);

        $ban->save();

        $request->session()->flash('success', "Ban " . $banFlash);
        return redirect()->action('AdminController@getLadderPlayer', ['ladderId' => $ladderId, 'playerId' => $playerId]);
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

    public function editPlayerName(Request $request)
    {
        $this->validate($request, [
            'player_name' => 'required|string|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/|max:11', //\x7c = | aka pipe
        ]);

        $history = \App\Models\LadderHistory::where('id', $request->history_id)->first();

        $player = \App\Models\Player::where('id', $request->player_id)
            ->where('ladder_id', $history->ladder->id)
            ->first();

        if ($player == null)
        {
            $request->session()->flash('error', "No existing player found with this player id");
            return redirect()->back();
        }

        $playerCaches = \App\Models\PlayerCache::where('player_id', $player->id)->get();

        if ($playerCaches == null || $playerCaches->count() == 0)
        {
            $request->session()->flash('error', "No player caches found with this player id");
            return redirect()->back();
        }

        $newName = $request->player_name;
        $newName = trim($newName);

        //check if this name already belongs to another player in this ladder
        $existing_players_count = \App\Models\Player::where('username', $newName)
            ->where('ladder_id', $history->ladder->id)
            ->count();
        if ($existing_players_count > 0)
        {
            $request->session()->flash('error', "This username is already taken for this ladder.");
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

        $url = \App\Models\URLHelper::getPlayerProfileUrl($history, $player->username);
        $request->session()->flash('success', "Player name has been updated to " . $player->username);
        return redirect()->to($url);
    }
}

function ini_to_b($string)
{
    if ($string == "Null") return null;
    if ($string == "Random") return -1;
    return $string == "Yes" ? true : false;
}
