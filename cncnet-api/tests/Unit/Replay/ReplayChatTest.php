<?php

namespace Tests\Unit\Replay;

use App\Http\Services\Replay\ReplayChat;
use PHPUnit\Framework\TestCase;

class ReplayChatTest extends TestCase
{
    public function test_fingerprint_matches_the_spawner(): void
    {
        // A line and the ChatFingerprint event the spawner queued for it, from a ladder replay.
        $salt = hex2bin('b72ae9e0f2ab38ca5bf69a8da575a67d');
        $this->assertSame('6bca71835dc58e50f15c52cf3d802617', bin2hex(ReplayChat::fingerprint($salt, 2, 'who ')));
    }

    public function test_merge_takes_each_line_once_from_every_copy(): void
    {
        $all = $this->line(100, 0, 'gl hf', 'a', label: 1);
        $teamA = $this->line(200, 0, 'push left', 'b', label: 2);
        $teamB = $this->line(300, 1, 'rush them', 'c', label: 2);
        $late = $this->line(900, 1, 'gg', 'd', label: 1);

        $merged = ReplayChat::merge([
            // Team A's player, who left before the last line.
            10 => [$all, $teamA],
            // Team B's player received the All line 12 frames after it was sent.
            11 => [['f' => 112, 't' => 112 / 60] + $all, $teamB, $late],
        ]);

        $this->assertSame(['gl hf', 'push left', 'rush them', 'gg'], array_column($merged, 'text'));
        $this->assertSame(100, $merged[0]['f']);
        $this->assertSame(['All', 'Team', 'Team', 'All'], array_column($merged, 'label'));
        $this->assertSame([true, true, true, true], array_column($merged, 'verified'));
    }

    public function test_an_edited_copy_verifies_nothing(): void
    {
        $forged = $this->line(50, 1, 'I resign', 'e');

        $merged = ReplayChat::merge([10 => [$this->line(100, 0, 'hi', 'a')], 11 => [$forged]], [11]);

        $this->assertSame(['I resign', 'hi'], array_column($merged, 'text'));
        $this->assertSame([false, true], array_column($merged, 'verified'));
    }

    public function test_unsalted_lines_match_by_sender_text_and_time(): void
    {
        $gg = fn (int $f) => ['key' => null, 'verified' => false] + $this->line($f, 0, 'gg', 'x');

        $merged = ReplayChat::merge([
            10 => [$gg(1000), $gg(5000)],
            11 => [$gg(1020), $gg(5030)],
        ]);

        // Two lines, each seen by both copies, not four - and not one either.
        $this->assertSame([1000, 5000], array_column($merged, 'f'));
        $this->assertSame([false, false], array_column($merged, 'verified'));
    }

    private function line(int $frame, int $house, string $text, string $key, int $label = 0): array
    {
        return [
            'f' => $frame, 't' => $frame / 60, 'house' => $house, 'name' => "Player {$house}", 'color' => 3,
            'label' => $label, 'recipients' => 0, 'text' => $text, 'key' => "{$house}:{$key}", 'verified' => true,
        ];
    }
}
