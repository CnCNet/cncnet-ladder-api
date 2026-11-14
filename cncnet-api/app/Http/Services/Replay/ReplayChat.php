<?php

namespace App\Http\Services\Replay;

/**
 * The in-game chat of a game, gathered from its players' replays.
 *
 * Chat travels outside the lockstep, so each recording holds only the lines its own player sent or
 * received: a team's messages are only in that team's copies, and a player who left early has none of
 * what was said after. The whole conversation is the union of every copy, each line once.
 *
 * Every line the spawner records carries a random salt, and when it is sent its sender queues a
 * ChatFingerprint event - the first 16 bytes of SHA-256("YRCHAT1" || salt || house || UTF-16LE text) -
 * that every player's copy holds on the same frame (yrpp-spawner docs/replay-format.md, "Chat
 * fingerprints"). That fingerprint is how the same line is recognised across copies, and a line whose
 * copy carries its fingerprint was really sent in the game rather than added to the file afterwards.
 * The analyser's Canonical.VerifyChat and CompareChat are the same checks.
 */
class ReplayChat
{
    /** Bumped whenever what is stored per copy changes, so stored chat can be read again. */
    public const VERSION = 1;

    private const MAX_MESSAGES = 2000;

    // A line recorded without a salt has no fingerprint to match it by. Its copies are the same
    // sender and text recorded within this many frames of each other: a sender's copy notes a line
    // as it is sent, a recipient's as it arrives.
    private const UNSALTED_MATCH_FRAMES = 600;

    // A line's fingerprint runs in the lockstep a little after the line is sent: 1 to 51 frames later
    // across the November 2026 ladder replays, 16 typically. A recording that stopped sooner than this
    // after a line cannot hold its fingerprint, so its lack says nothing about the line.
    private const FINGERPRINT_DELAY_FRAMES = 180;

    public const LABELS = [1 => 'All', 2 => 'Team', 3 => 'Private'];

    /**
     * The chat lines one recording holds, in the order it recorded them: frame 'f', game time 't' in
     * seconds, the sender's house, name and colour scheme, 'label' and 'recipients' as the sender gave
     * them, the text, the line's fingerprint 'key' (hex, null without a salt), whether this copy's
     * event stream carries that fingerprint ('verified'), and if not, whether the recording stopped too
     * soon after the line to hold it ('late').
     */
    public static function read(ReplayFileReader $reader): array
    {
        $header = $reader->header();

        $speedSegments = [[0, 0.0, ReplayFileReader::framesPerSecond($header['gameSpeed'])]];
        $secondsAt = function (int $frame) use (&$speedSegments): float
        {
            $segment = $speedSegments[0];
            foreach ($speedSegments as $s)
            {
                if ($s[0] > $frame)
                {
                    break;
                }
                $segment = $s;
            }
            return $segment[1] + ($frame - $segment[0]) / $segment[2];
        };

        $messages = [];
        $fingerprints = [];

        $walk = $reader->walkFrames(function (int $frame, array $record) use (&$speedSegments, $secondsAt, &$messages, &$fingerprints)
        {
            if (isset($record['gameSpeed']))
            {
                $fps = ReplayFileReader::framesPerSecond($record['gameSpeed']);
                if ($fps !== end($speedSegments)[2])
                {
                    $speedSegments[] = [$frame, $secondsAt($frame), $fps];
                }
            }

            foreach ($record['events'] ?? [] as $event)
            {
                if (ord($event[0]) === ReplayFileReader::EVENT_CHAT_FINGERPRINT)
                {
                    $hash = substr($event, ReplayFileReader::EVENT_DATA_OFFSET, ReplayFileReader::CHAT_FINGERPRINT_BYTES);
                    $fingerprints[ord($event[2]) . ':' . bin2hex($hash)] = true;
                }
            }

            foreach ($record['sideChannel'] ?? [] as $s)
            {
                if ($s['type'] !== ReplayFileReader::SIDE_CHANNEL_CHAT || count($messages) >= self::MAX_MESSAGES)
                {
                    continue;
                }

                $messages[] = [
                    'f'          => $frame,
                    't'          => round($secondsAt($frame), 1),
                    'house'      => $s['house'],
                    'name'       => $s['name'],
                    'color'      => $s['aux'],
                    'label'      => $s['label'],
                    'recipients' => $s['recipients'],
                    'text'       => $s['text'],
                    'key'        => $s['salt'] === null ? null
                        : ($s['house'] & 0xFF) . ':' . bin2hex(self::fingerprint($s['salt'], $s['house'], $s['text'])),
                ];
            }
        });

        foreach ($messages as &$m)
        {
            $m['verified'] = $m['key'] !== null && isset($fingerprints[$m['key']]);
            $m['late'] = $m['key'] !== null && !$m['verified'] && $walk['lastFrame'] - $m['f'] < self::FINGERPRINT_DELAY_FRAMES;
        }
        unset($m);

        return $messages;
    }

    /** The first 16 bytes of SHA-256("YRCHAT1" || salt || uint8 house || UTF-16LE text). */
    public static function fingerprint(string $salt, int $house, string $text): string
    {
        $input = 'YRCHAT1' . $salt . chr($house & 0xFF) . mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
        return substr(hash('sha256', $input, true), 0, ReplayFileReader::CHAT_FINGERPRINT_BYTES);
    }

    /**
     * One conversation from several copies' lines, each line once and in the order it was said. A line
     * is verified when some copy that holds it carries its fingerprint; a copy in $untrusted - one
     * edited after the game, going by its seals - verifies nothing. An unverified line is 'late' when
     * every trusted copy that holds it stopped too soon after it to hold the fingerprint: sent as the
     * game ended, rather than missing a fingerprint it should have.
     *
     * @param array $copies [copyId => lines from read()]
     */
    public static function merge(array $copies, array $untrusted = []): array
    {
        $merged = [];
        $byKey = [];

        foreach ($copies as $copyId => $lines)
        {
            $trusted = !in_array($copyId, $untrusted, true);
            $claimed = [];

            foreach ($lines as $line)
            {
                $index = $line['key'] !== null
                    ? ($byKey[$line['key']] ?? null)
                    : self::findUnsalted($merged, $line, $claimed);

                if ($index === null)
                {
                    $index = count($merged);
                    $merged[] = array_merge($line, ['verified' => false, 'checked' => false, 'lateCopies' => 0]);
                    if ($line['key'] !== null)
                    {
                        $byKey[$line['key']] = $index;
                    }
                }
                else
                {
                    $m = &$merged[$index];
                    // The earliest copy of a line is nearest when it was typed: the sender's.
                    if ($line['f'] < $m['f'])
                    {
                        $m['f'] = $line['f'];
                        $m['t'] = $line['t'];
                    }
                    $m['label'] = $m['label'] ?: $line['label'];
                    $m['recipients'] |= $line['recipients'];
                    unset($m);
                }

                $claimed[$index] = true;
                if ($trusted && !empty($line['verified']))
                {
                    $merged[$index]['verified'] = true;
                }
                if ($trusted)
                {
                    if (!empty($line['late']))
                    {
                        $merged[$index]['lateCopies']++;
                    }
                    else
                    {
                        $merged[$index]['checked'] = true;
                    }
                }
            }
        }

        usort($merged, fn ($a, $b) => [$a['f'], $a['house']] <=> [$b['f'], $b['house']]);

        return array_map(fn ($m) => [
            'f'        => $m['f'],
            't'        => $m['t'],
            'house'    => $m['house'],
            'name'     => $m['name'],
            'color'    => $m['color'],
            'label'    => self::LABELS[$m['label']] ?? null,
            'text'     => $m['text'],
            'verified' => $m['verified'],
            'late'     => !$m['verified'] && !$m['checked'] && $m['lateCopies'] > 0,
        ], $merged);
    }

    /** The unsalted line from another copy that this one is, if any this copy has not already matched. */
    private static function findUnsalted(array $merged, array $line, array $claimed): ?int
    {
        $best = null;
        foreach ($merged as $i => $m)
        {
            if ($m['key'] !== null || isset($claimed[$i]) || $m['house'] !== $line['house'] || $m['text'] !== $line['text'])
            {
                continue;
            }
            $distance = abs($m['f'] - $line['f']);
            if ($distance <= self::UNSALTED_MATCH_FRAMES && ($best === null || $distance < $best[1]))
            {
                $best = [$i, $distance];
            }
        }
        return $best[0] ?? null;
    }
}
