<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Qm\RescanChatFlagsJob;
use App\Models\ChatFilterSettings;
use App\Models\ChatFilterTerm;
use App\Models\Game;
use App\Models\GameChatFlag;
use App\Models\Ladder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The chat filter's word list and spam limits (admins), and the games it flagged (anyone who
 * moderates a ladder, for the ladders they moderate - the same people who see a game's chat).
 */
class ChatFlagController extends Controller
{
    private const STATUSES = [GameChatFlag::OPEN, GameChatFlag::REVIEWED, GameChatFlag::DISMISSED];
    private const GAMES_PER_PAGE = 25;

    public function getFilter()
    {
        $terms = ChatFilterTerm::orderBy('term')->get();

        return view('admin.chat-filter', [
            'wholeWords' => $terms->where('whole_word', true)->pluck('term')->implode("\n"),
            'anywhere'   => $terms->where('whole_word', false)->pluck('term')->implode("\n"),
            'settings'   => ChatFilterSettings::current(),
        ]);
    }

    public function saveFilter(Request $request)
    {
        $limits = $request->validate([
            'burst_messages'  => 'required|integer|min:2|max:100',
            'burst_seconds'   => 'required|integer|min:1|max:600',
            'repeat_messages' => 'required|integer|min:2|max:100',
            'repeat_seconds'  => 'required|integer|min:1|max:3600',
            'whole_words'     => 'nullable|string|max:20000',
            'anywhere'        => 'nullable|string|max:20000',
        ]);

        $lines = fn (?string $text) => collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($term) => mb_substr(trim($term), 0, 100))
            ->filter(fn ($term) => $term !== '')
            ->unique(fn ($term) => mb_strtolower($term));

        DB::transaction(function () use ($limits, $lines)
        {
            ChatFilterTerm::query()->delete();
            foreach ([true => $limits['whole_words'] ?? '', false => $limits['anywhere'] ?? ''] as $wholeWord => $text)
            {
                foreach ($lines($text) as $term)
                {
                    ChatFilterTerm::create(['term' => $term, 'whole_word' => (bool) $wholeWord]);
                }
            }

            $settings = ChatFilterSettings::query()->first() ?? new ChatFilterSettings();
            $settings->fill(collect($limits)->only(array_keys(ChatFilterSettings::DEFAULTS))->all())->save();
        });

        RescanChatFlagsJob::dispatch();

        return redirect()->route('admin.chat-filter')
            ->with('status', 'Saved. Every game\'s chat is being checked again; the flagged list updates in a few minutes.');
    }

    public function getFlags(Request $request)
    {
        $user = Auth::user();
        $ladderIds = $this->moderatedLadderIds($user);
        if ($ladderIds !== null && count($ladderIds) === 0)
        {
            abort(401);
        }

        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : GameChatFlag::OPEN;
        $ladderId = $request->query('ladder') ? (int) $request->query('ladder') : null;

        $scope = GameChatFlag::query()->where('status', $status)
            ->when($ladderIds !== null, fn ($q) => $q->whereIn('ladder_id', $ladderIds))
            ->when($ladderId !== null, fn ($q) => $q->where('ladder_id', $ladderId));

        $games = (clone $scope)->select('game_id', DB::raw('MAX(created_at) AS flagged_at'))
            ->groupBy('game_id')->orderByDesc('flagged_at')
            ->paginate(self::GAMES_PER_PAGE)->withQueryString();

        $flags = (clone $scope)->whereIn('game_id', $games->pluck('game_id'))
            ->with('reviewer')->orderBy('frame')->get()->groupBy('game_id');

        $gameRows = Game::with('ladderHistory.ladder')->whereIn('id', $games->pluck('game_id'))->get()->keyBy('id');

        $ladders = Ladder::query()->when($ladderIds !== null, fn ($q) => $q->whereIn('id', $ladderIds))
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.chat-flags', compact('games', 'flags', 'gameRows', 'ladders', 'status', 'ladderId'));
    }

    /** Marks every flag of a game reviewed or dismissed, or opens them again. */
    public function reviewFlags(Request $request, int $gameId)
    {
        $action = $request->validate(['status' => 'required|in:' . implode(',', self::STATUSES)])['status'];

        $user = Auth::user();
        $ladderIds = $this->moderatedLadderIds($user);
        $flags = GameChatFlag::where('game_id', $gameId)
            ->when($ladderIds !== null, fn ($q) => $q->whereIn('ladder_id', $ladderIds));

        if (!(clone $flags)->exists())
        {
            abort(404);
        }

        $flags->update([
            'status'      => $action,
            'reviewed_by' => $action === GameChatFlag::OPEN ? null : $user->id,
            'reviewed_at' => $action === GameChatFlag::OPEN ? null : now(),
        ]);

        return redirect()->back();
    }

    /** Null for site staff, who see every ladder; otherwise the ladders this user moderates or runs. */
    private function moderatedLadderIds(User $user): ?array
    {
        if ($user->isModerator())
        {
            return null;
        }

        return $user->ladderAdmins()
            ->where(fn ($q) => $q->where('admin', true)->orWhere('moderator', true))
            ->pluck('ladder_id')->all();
    }
}
