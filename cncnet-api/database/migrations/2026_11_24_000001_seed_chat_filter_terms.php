<?php

use App\Http\Services\Replay\ChatFilter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Starts the chat filter with a stock English list, so staff do not have to type one in. The list
     * is database/seed/chat_filter_terms_en.txt, from github.com/coffee-and-fun/google-profanity-words
     * (MIT). Only seeds an empty list, so it never overwrites words staff have saved.
     */
    private const FILE = 'seed/chat_filter_terms_en.txt';

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

        $lines = preg_split('/\r\n|\r|\n/', (string) file_get_contents(database_path(self::FILE)));

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
