<?php

namespace App\Http\Services\Replay;

/**
 * Finds what staff want to see in a game's chat: lines with a word from their list, a sender posting
 * a burst of lines, and a sender repeating one line. Works on the lines ReplayChat::merge gives.
 *
 * Words are matched the way players dodge a filter: case and accents are ignored, digits and symbols
 * that stand in for letters count as those letters (4 for a, $ for s), a letter may be repeated, and
 * up to two spaces or symbols may sit between letters - so "Sh1t", "shiiit" and "s.h.i.t" all match
 * "shit". A whole-word term matches only where no letter or digit adjoins it.
 */
class ChatFilter
{
    public const WORD = 'word';
    public const BURST = 'burst';
    public const REPEAT = 'repeat';

    private const DIGITS = ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '8' => 'b'];

    // Read as letters in one pass and left as they are in another, since "!" is also just "!".
    private const SYMBOLS = ['@' => 'a', '$' => 's', '!' => 'i', '|' => 'i', '€' => 'e'];

    private const MAX_TEXT = 1000;

    /** @var array [term => regex] */
    private array $patterns = [];

    /**
     * @param array $terms [['term' => string, 'whole_word' => bool], ...]
     */
    public function __construct(array $terms)
    {
        foreach ($terms as $t)
        {
            $normal = self::normalise((string) $t['term'], false);
            if (trim($normal) !== '')
            {
                $this->patterns[$t['term']] = self::pattern(trim($normal), (bool) $t['whole_word']);
            }
        }
    }

    /**
     * Lower case, without accents, with look-alike digits - and symbols, unless $symbols is false -
     * read as the letters they stand for.
     */
    public static function normalise(string $text, bool $symbols = true): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        if (class_exists(\Normalizer::class))
        {
            $decomposed = \Normalizer::normalize($text, \Normalizer::FORM_D);
            if ($decomposed !== false)
            {
                $text = preg_replace('/\p{Mn}+/u', '', $decomposed);
            }
        }
        return strtr($text, $symbols ? self::DIGITS + self::SYMBOLS : self::DIGITS);
    }

    /** The terms a line contains. */
    public function match(string $text): array
    {
        $readings = [self::normalise($text), self::normalise($text, false)];
        $found = [];
        foreach ($this->patterns as $term => $pattern)
        {
            if (preg_match($pattern, $readings[0]) || preg_match($pattern, $readings[1]))
            {
                $found[] = $term;
            }
        }
        return $found;
    }

    /**
     * Every flag in a game's chat, each with 'reason', the sender's 'house' and 'name', the frame 'f'
     * and game time 't' of its (first) line, a 'detail' and the 'text'.
     *
     * @param array $lines from ReplayChat::merge, in order
     */
    public function flags(array $lines, int $burstMessages, int $burstSeconds, int $repeatMessages, int $repeatSeconds): array
    {
        $flags = [];

        foreach ($lines as $line)
        {
            $terms = $this->match($line['text']);
            if (count($terms) > 0)
            {
                $flags[] = self::flag(self::WORD, [$line], implode(', ', $terms));
            }
        }

        foreach (self::bySender($lines, fn ($line) => $line['house']) as $sent)
        {
            foreach (self::runs($sent, $burstMessages, $burstSeconds) as $run)
            {
                $flags[] = self::flag(self::BURST, $run, count($run) . ' lines in ' . self::span($run) . ' s');
            }
        }

        $sameLine = fn ($line) => $line['house'] . '|' . preg_replace('/\s+/u', ' ', trim(self::normalise($line['text'])));
        foreach (self::bySender($lines, $sameLine) as $sent)
        {
            foreach (self::runs($sent, $repeatMessages, $repeatSeconds) as $run)
            {
                $flags[] = self::flag(self::REPEAT, [$run[0]], count($run) . ' times in ' . self::span($run) . ' s');
            }
        }

        usort($flags, fn ($a, $b) => [$a['f'], $a['reason']] <=> [$b['f'], $b['reason']]);
        return $flags;
    }

    /**
     * Runs of at least $count lines with no more than $seconds from the first to the last. Each run
     * takes every line it can, and the next run starts after it, so a long flood is one flag.
     */
    private static function runs(array $lines, int $count, int $seconds): array
    {
        if ($count < 2)
        {
            return [];
        }

        $runs = [];
        $n = count($lines);
        for ($i = 0; $i < $n;)
        {
            $j = $i;
            while ($j + 1 < $n && $lines[$j + 1]['t'] - $lines[$i]['t'] <= $seconds)
            {
                $j++;
            }

            if ($j - $i + 1 >= $count)
            {
                $runs[] = array_slice($lines, $i, $j - $i + 1);
                $i = $j + 1;
            }
            else
            {
                $i++;
            }
        }
        return $runs;
    }

    private static function bySender(array $lines, callable $key): array
    {
        $groups = [];
        foreach ($lines as $line)
        {
            $groups[$key($line)][] = $line;
        }
        return $groups;
    }

    private static function span(array $run): int
    {
        return (int) round(end($run)['t'] - $run[0]['t']);
    }

    private static function flag(string $reason, array $lines, string $detail): array
    {
        $text = implode("\n", array_column($lines, 'text'));
        return [
            'reason' => $reason,
            'house'  => $lines[0]['house'],
            'name'   => $lines[0]['name'],
            'f'      => $lines[0]['f'],
            't'      => $lines[0]['t'],
            'detail' => mb_substr($detail, 0, 255),
            'text'   => mb_substr($text, 0, self::MAX_TEXT),
        ];
    }

    private static function pattern(string $term, bool $wholeWord): string
    {
        $parts = [];
        foreach (preg_split('//u', $term, -1, PREG_SPLIT_NO_EMPTY) as $c)
        {
            if (preg_match('/[\p{L}\p{N}]/u', $c))
            {
                $parts[] = preg_quote($c, '/') . '+';
            }
            elseif (preg_match('/\s/u', $c))
            {
                $parts[] = '[^\p{L}\p{N}]*';
            }
            else
            {
                $parts[] = preg_quote($c, '/');
            }
        }

        $body = implode('[^\p{L}\p{N}]{0,2}', $parts);
        return $wholeWord
            ? '/(?<![\p{L}\p{N}])' . $body . '(?![\p{L}\p{N}])/u'
            : '/' . $body . '/u';
    }
}
