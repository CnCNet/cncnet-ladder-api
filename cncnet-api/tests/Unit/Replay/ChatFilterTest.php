<?php

namespace Tests\Unit\Replay;

use App\Http\Services\Replay\ChatFilter;
use PHPUnit\Framework\TestCase;

class ChatFilterTest extends TestCase
{
    public function test_words_match_through_the_usual_disguises(): void
    {
        $filter = new ChatFilter([['term' => 'shit', 'whole_word' => true], ['term' => 'noob', 'whole_word' => false]]);

        foreach (['shit', 'SHIT!', 'sh1t', 'shiiit', 's.h.i.t', 's h i t', 'shít'] as $text)
        {
            $this->assertSame(['shit'], $filter->match($text), $text);
        }
        foreach (['shitake', 'bullshitter', 'shirt'] as $text)
        {
            $this->assertNotContains('shit', $filter->match($text), $text);
        }
        $this->assertSame(['noob'], $filter->match('total n00bs'));
    }

    public function test_bursts_and_repeats_are_one_flag_per_run(): void
    {
        $filter = new ChatFilter([]);
        $lines = [];
        // Player 0 sends "yo" 7 times in 3 seconds; player 1 sends 3 lines across a minute.
        for ($i = 0; $i < 7; $i++)
        {
            $lines[] = $this->line(600 + $i * 25, 0, 'yo');
        }
        foreach ([1000, 2000, 3000] as $f)
        {
            $lines[] = $this->line($f, 1, 'gg');
        }

        $flags = $filter->flags($lines, 7, 10, 4, 60);

        $this->assertSame(['burst', 'repeat'], array_column($flags, 'reason'));
        $this->assertSame([0, 0], array_column($flags, 'house'));
        $this->assertSame('7 lines in 3 s', $flags[0]['detail']);
        $this->assertSame('7 times in 3 s', $flags[1]['detail']);
    }

    private function line(int $frame, int $house, string $text): array
    {
        return ['f' => $frame, 't' => $frame / 60, 'house' => $house, 'name' => "Player {$house}", 'text' => $text];
    }
}
