<?php

use App\Http\Services\Replay\ChatFilter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Starts the chat filter with a stock English list, so staff do not have to type one in. The list
     * is downloaded from github.com/coffee-and-fun/google-profanity-words (MIT) rather than kept in
     * this repository, pinned to one commit so every install gets the same words. Only seeds an empty
     * list, so it never overwrites words staff have saved.
     */
    private const URL = 'https://raw.githubusercontent.com/coffee-and-fun/google-profanity-words/5b2c59a78e777972f5bfafdf0e52919d1b72c99d/data/en.txt';

    // Left out because they would flag ordinary game chat: "nob" matches "n00b", "ho" matches "hooo".
    private const SKIP = [
        'bloody', 'bum', 'butt', 'crap', 'damn', 'domination', 'hell', 'ho', 'lmfao', 'nob', 'poo',
        'suck', 'sucks', 'xx', 'xxx',
    ];

    public function up(): void
    {
        if (DB::table('chat_filter_terms')->exists())
        {
            return;
        }

        // A failed download leaves the list empty rather than failing the deploy; staff can still type
        // words in, or roll this migration back and run it again.
        try
        {
            $response = Http::timeout(30)->retry(3, 1000)->get(self::URL)->throw();
        }
        catch (\Throwable $e)
        {
            Log::warning('Chat filter word list not seeded: ' . $e->getMessage());
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', $response->body());

        // The list spells some words several ways ("5h1t", "s hit", "shit"), which the filter reads as
        // one since it skips spaces and symbols between letters, so keep one of each - the plain
        // spelling where there is one.
        $terms = [];
        foreach ($lines as $line)
        {
            $term = mb_substr(trim($line), 0, 100);
            if ($term === '' || in_array(mb_strtolower($term), self::SKIP, true))
            {
                continue;
            }

            $key = preg_replace('/[^\p{L}\p{N}]+/u', '', ChatFilter::normalise($term, false));
            $key = $key === '' ? $term : $key;
            if (!isset($terms[$key]) || $term === $key)
            {
                $terms[$key] = $term;
            }
        }

        $now = now();
        $rows = array_map(fn ($term) => ['term' => $term, 'whole_word' => true, 'created_at' => $now, 'updated_at' => $now],
            array_values($terms));

        foreach (array_chunk($rows, 500) as $chunk)
        {
            DB::table('chat_filter_terms')->insert($chunk);
        }
    }

    public function down(): void
    {
        // Staff may have edited the list since, so leave it.
    }
};
