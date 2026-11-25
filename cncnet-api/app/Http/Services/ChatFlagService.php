<?php

namespace App\Http\Services;

use App\Http\Services\Replay\ChatFilter;
use App\Models\ChatFilterSettings;
use App\Models\ChatFilterTerm;
use App\Models\Game;
use App\Models\GameChatFlag;
use App\Models\GameReplayChat;
use Illuminate\Support\Facades\DB;

/**
 * Keeps each game's chat flags in step with its chat and the staff's word list and spam limits.
 * Run after every replay upload, since each copy can add lines, and over every game when the list or
 * the limits change.
 */
class ChatFlagService
{
    private ?ChatFilter $filter = null;
    private ?ChatFilterSettings $settings = null;

    /**
     * Re-reads one game's flags. A flag staff have reviewed or dismissed keeps that status, and stays
     * when the list no longer flags it; an open one that no longer applies goes. Returns how many
     * flags the chat has now.
     */
    public function refresh(int $gameId): int
    {
        $chat = (new ReplayService())->chat($gameId);
        $settings = $this->settings();
        $found = $this->filter()->flags($chat['lines'] ?? [],
            $settings->burst_messages, $settings->burst_seconds, $settings->repeat_messages, $settings->repeat_seconds);

        $byKey = [];
        foreach ($found as $flag)
        {
            $byKey += ["{$flag['reason']}|{$flag['house']}|{$flag['f']}" => $flag];
        }

        $ladderId = Game::with('ladderHistory')->find($gameId)?->ladderHistory?->ladder_id;

        DB::transaction(function () use ($gameId, $byKey, $ladderId)
        {
            $existing = GameChatFlag::where('game_id', $gameId)->get()->keyBy(fn (GameChatFlag $f) => $f->key());

            foreach ($existing as $key => $flag)
            {
                if (!isset($byKey[$key]) && $flag->status === GameChatFlag::OPEN)
                {
                    $flag->delete();
                }
            }

            foreach ($byKey as $key => $flag)
            {
                $values = [
                    'ladder_id' => $ladderId,
                    'name'      => mb_substr($flag['name'], 0, 64),
                    'seconds'   => (int) $flag['t'],
                    'detail'    => $flag['detail'],
                    'text'      => $flag['text'],
                ];

                if (isset($existing[$key]))
                {
                    $existing[$key]->update($values);
                }
                else
                {
                    GameChatFlag::create($values + [
                        'game_id' => $gameId,
                        'reason'  => $flag['reason'],
                        'house'   => $flag['house'],
                        'frame'   => $flag['f'],
                        'status'  => GameChatFlag::OPEN,
                    ]);
                }
            }
        });

        return count($byKey);
    }

    /** Re-reads every game whose chat has been read. Returns how many games have flags. */
    public function refreshAll(): int
    {
        $flagged = 0;
        GameReplayChat::query()->select('game_id')->distinct()->orderBy('game_id')
            ->chunk(500, function ($rows) use (&$flagged)
            {
                foreach ($rows as $row)
                {
                    $flagged += $this->refresh((int) $row->game_id) > 0 ? 1 : 0;
                }
            });
        return $flagged;
    }

    private function filter(): ChatFilter
    {
        return $this->filter ??= new ChatFilter(ChatFilterTerm::all(['term', 'whole_word'])->toArray());
    }

    private function settings(): ChatFilterSettings
    {
        return $this->settings ??= ChatFilterSettings::current();
    }
}
