<?php

namespace App\Http\Services\Replay;

/**
 * The canonical form of a recording - the part every player's copy of the same game shares - and the
 * SHA-256 chain the recorder seals over it. A mirror of yrpp-spawner src/Replay/ReplayCanonical.cpp
 * (and the analyser's Canonical.cs); docs/replay-format.md, "Canonical hash", is the specification.
 * Any difference makes every seal disagree, so a change there is a change here.
 *
 * The ladder recomputes the chain rather than trusting the seals a file carries: a copy edited without
 * redoing its chain no longer matches its own seals, and one whose chain was redone no longer matches
 * the other players' copies.
 */
class ReplayCanonical
{
    private const DESCRIPTOR_VERSION = 1;

    private const EVENT_MEGAMISSION = 0x04;

    // MEGAMISSION payload offsets: Whom ends at 5, byte 6 is the receiver's unit-list slot, Follow is
    // bytes 17-21.
    private const MEGAMISSION_WHOM_END = 5;
    private const MEGAMISSION_SLOT = 6;
    private const MEGAMISSION_FOLLOW_BEGIN = 17;
    private const MEGAMISSION_FOLLOW_END = 22;

    // The network's own events, which each peer sees its own set of.
    private const NETWORK_EVENTS = [0, 25, 26, 27, 28, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41];
    private const VANILLA_EVENT_TYPES = 47;

    private const PLAYER_SECTIONS = ['settings', 'other1', 'other2', 'other3', 'other4', 'other5', 'other6', 'other7'];

    /**
     * Recomputes a recording's chain and checks the file against it.
     *
     * Returns 'seals' - [throughFrame => hex hash] for every interval the recording completed, then,
     * for one that reached its end, the final seal over whatever followed - and 'consistent': false
     * when a seal the file carries is not the one its contents give, which is a file edited after the
     * game. Null when the file carries no seals at all, which nothing written by this format does.
     */
    public static function verify(ReplayFileReader $reader): ?array
    {
        $previous = hash('sha256', self::descriptor($reader), true);
        $window = hash_init('sha256');
        hash_update($window, $previous);
        $intervalEnd = ReplayFileReader::SEAL_INTERVAL_FRAMES;

        $computed = [];
        $claimed = [];
        $lastFrame = -1;

        $close = function (int $through) use (&$previous, &$window, &$computed)
        {
            $previous = hash_final($window, true);
            $computed[$through] = bin2hex($previous);
            $window = hash_init('sha256');
            hash_update($window, $previous);
        };

        $walk = $reader->walkFrames(function (int $frame, array $record) use (&$intervalEnd, &$window, &$claimed, &$lastFrame, $close)
        {
            foreach ($record['seals'] ?? [] as $through => $hash)
            {
                $claimed[$through] = $hash;
            }

            $lastFrame = $frame;
            $bytes = self::frameBytes($frame, $record);
            if ($bytes === null)
            {
                return;
            }

            while ($frame >= $intervalEnd)
            {
                $close($intervalEnd - 1);
                $intervalEnd += ReplayFileReader::SEAL_INTERVAL_FRAMES;
            }
            hash_update($window, $bytes);
        });

        // The recorder ends the chain at the last frame it wrote a record for. Only a clean close
        // writes that seal, into the statistics section along with all the others.
        if ($walk['sawEnd'] && $lastFrame >= 0)
        {
            $close($lastFrame);
            $claimed += $reader->statistics()['seals'] ?? [];
        }

        if (count($claimed) === 0)
        {
            return null;
        }

        $consistent = true;
        foreach ($claimed as $through => $hash)
        {
            if (($computed[$through] ?? null) !== $hash)
            {
                $consistent = false;
                break;
            }
        }

        ksort($computed);
        return ['seals' => $computed, 'consistent' => $consistent];
    }

    public static function isCanonicalEventType(int $type): bool
    {
        if (in_array($type, self::NETWORK_EVENTS, true))
        {
            return false;
        }
        return $type < self::VANILLA_EVENT_TYPES || $type === ReplayFileReader::EVENT_CHAT_FINGERPRINT;
    }

    /** The description of the game the chain starts from. */
    private static function descriptor(ReplayFileReader $reader): string
    {
        $h = $reader->header();
        $out = 'YRRPSEAL' . pack('V', self::DESCRIPTOR_VERSION)
            . pack('V', $h['gameMode'])
            . pack('V', $h['uniqueIdCounter'] & 0xFFFFFFFF)
            . pack('V', $h['seed'] & 0xFFFFFFFF)
            . pack('V', $h['randomNext1'] & 0xFFFFFFFF)
            . pack('V', $h['randomNext2'] & 0xFFFFFFFF)
            . $h['randomizerTable']
            . pack('V', $h['gameSpeed'])
            . hash('sha256', $reader->spawnMap(), true);

        $entries = [];
        foreach (self::players($reader->spawnIni()) as $p)
        {
            $entries[] = chr($p['human'] ? 1 : 0) . chr($p['observer'] ? 1 : 0)
                . pack('V', $p['color'] & 0xFFFFFFFF)
                . pack('V', $p['country'] & 0xFFFFFFFF)
                . pack('V', $p['difficulty'] & 0xFFFFFFFF)
                . pack('V', strlen($p['name'])) . $p['name'];
        }

        // Sorted as byte strings, so the order is nobody's: each copy lists its own player first.
        usort($entries, 'strcmp');
        return $out . pack('V', count($entries)) . implode('', $entries);
    }

    /** The players spawn.ini describes, read the way the spawner's ReadCanonicalPlayers reads them. */
    private static function players(string $spawnIni): array
    {
        $ini = self::parseIni($spawnIni);
        $players = [];

        for ($i = 0; $i < 8; $i++)
        {
            $section = self::PLAYER_SECTIONS[$i];
            $tag = 'multi' . ($i + 1);
            $name = $ini[$section]['name'] ?? '';
            if ($name !== '')
            {
                $observer = $ini[$section]['isspectator'] ?? '';
                $players[] = [
                    'human'      => true,
                    'observer'   => $observer !== '' && str_contains('1tTyY', $observer[0]),
                    'color'      => self::readInt($ini, $section, 'color'),
                    'country'    => self::readInt($ini, $section, 'side'),
                    'difficulty' => -1,
                    'name'       => $name,
                ];
                continue;
            }

            $color = self::readInt($ini, 'housecolors', $tag);
            $country = self::readInt($ini, 'housecountries', $tag);
            $difficulty = self::readInt($ini, 'househandicaps', $tag);
            if ($color < 0 && $country < 0 && $difficulty < 0)
            {
                continue;
            }
            $players[] = ['human' => false, 'observer' => false, 'color' => $color, 'country' => $country,
                'difficulty' => $difficulty, 'name' => ''];
        }

        return $players;
    }

    /**
     * The spawner's deliberately small INI reader: [section] and key=value lines, names without case,
     * values trimmed of spaces, tabs and carriage returns, the first occurrence of a key kept.
     */
    private static function parseIni(string $text): array
    {
        $sections = [];
        $section = null;

        foreach (explode("\n", $text) as $rawLine)
        {
            $line = trim($rawLine, " \t\r");
            $length = strlen($line);
            if ($length >= 2 && $line[0] === '[' && $line[$length - 1] === ']')
            {
                $section = strtolower(trim(substr($line, 1, -1), " \t\r"));
                $sections[$section] ??= [];
                continue;
            }

            $equals = strpos($line, '=');
            if ($section === null || $equals === false)
            {
                continue;
            }
            $key = strtolower(trim(substr($line, 0, $equals), " \t\r"));
            $sections[$section][$key] ??= trim(substr($line, $equals + 1), " \t\r");
        }

        return $sections;
    }

    /** As strtol reads it: an optional sign, then digits, whatever follows ignored; -1 when absent. */
    private static function readInt(array $ini, string $section, string $key): int
    {
        $value = $ini[$section][$key] ?? null;
        if ($value === null)
        {
            return -1;
        }

        $value = ltrim($value, " \t\n\r\v\f");
        $negative = str_starts_with($value, '-');
        $i = ($negative || str_starts_with($value, '+')) ? 1 : 0;
        $result = 0;
        while ($i < strlen($value) && ctype_digit($value[$i]) && $result < 0x7FFFFFFF)
        {
            $result = $result * 10 + (ord($value[$i++]) - 48);
        }
        return max(-0x80000000, min(0x7FFFFFFF, $negative ? -$result : $result));
    }

    /** One frame's canonical bytes, or null when the frame is not part of the chain. */
    private static function frameBytes(int $frame, array $record): ?string
    {
        $events = [];
        foreach ($record['events'] ?? [] as $event)
        {
            if (self::isCanonicalEventType(ord($event[0])))
            {
                $events[] = $event;
            }
        }

        $hasHash = isset($record['crc']);
        if (!$hasHash && count($events) === 0)
        {
            return null;
        }

        $out = self::varint($frame) . chr($hasHash ? 1 : 0)
            . ($hasHash ? pack('v', $record['crc']) : '')
            . self::varint(count($events));

        $previous = null;
        $previousHouse = -1;
        foreach ($events as $event)
        {
            $type = ord($event[0]);
            $house = ord($event[2]);
            $payload = substr($event, ReplayFileReader::EVENT_DATA_OFFSET, ReplayFileReader::payloadLength($type));

            if ($type === self::EVENT_MEGAMISSION)
            {
                $payload[self::MEGAMISSION_SLOT] = "\0";

                // A group order travels as one full order and then the other units' Whom alone, so
                // every receiver gives the rest of the group the first one's Follow.
                $continuesGroup = $previous !== null && $previousHouse === $house
                    && substr($previous, self::MEGAMISSION_WHOM_END, self::MEGAMISSION_FOLLOW_BEGIN - self::MEGAMISSION_WHOM_END)
                        === substr($payload, self::MEGAMISSION_WHOM_END, self::MEGAMISSION_FOLLOW_BEGIN - self::MEGAMISSION_WHOM_END)
                    && substr($previous, self::MEGAMISSION_FOLLOW_END) === substr($payload, self::MEGAMISSION_FOLLOW_END);

                $previous = $payload;
                $previousHouse = $house;
                if ($continuesGroup)
                {
                    $payload = substr_replace($payload, str_repeat("\0", self::MEGAMISSION_FOLLOW_END - self::MEGAMISSION_FOLLOW_BEGIN),
                        self::MEGAMISSION_FOLLOW_BEGIN, self::MEGAMISSION_FOLLOW_END - self::MEGAMISSION_FOLLOW_BEGIN);
                }
            }
            else
            {
                $previous = null;
            }

            $out .= chr($type) . chr($house) . $payload;
        }

        return $out;
    }

    private static function varint(int $v): string
    {
        $out = '';
        while ($v >= 0x80)
        {
            $out .= chr(($v & 0x7F) | 0x80);
            $v >>= 7;
        }
        return $out . chr($v);
    }
}
