<?php

namespace App\Http\Services\Replay;

use RuntimeException;

/**
 * Reads the parts of a .yrrp replay the ladder uses: the header, the embedded INIs, the statistics
 * section and the frame stream.
 *
 * The layout mirrors yrpp-spawner/src/Replay/ReplayFormat.h and docs/replay-format.md there. The
 * header is plain; the two INIs and the statistics section are each raw deflate on their own; the
 * frame stream is one raw deflate stream of varint records whose blocks are tagged and carry their
 * own length, so a block this reader does not know is stepped over rather than stopping the walk.
 */
class ReplayFileReader
{
    public const MAGIC = 0x50525259; // 'YRRP'
    public const VERSION = 1;
    public const HEADER_SIZE = 1096;

    private const MAX_EMBEDDED_FILE_BYTES = 32 * 1024 * 1024;
    private const MAX_STATISTICS_BYTES = 8 * 1024 * 1024;
    // An hour of an eight-player game inflates to under ten.
    private const MAX_STREAM_BYTES = 128 * 1024 * 1024;
    private const MAX_GAME_SPEED = 6;
    private const MAX_EVENTS_PER_FRAME = 128 * 128;
    private const MAX_BLOCK_BYTES = 1024 * 1024;

    public const EVENT_SIZE = 111;
    public const EVENT_DATA_OFFSET = 7;
    private const EVENT_FRAMEINFO = 0x1C;

    // EventClass::EventLength, then the spawner's own types: the payload bytes stored per event.
    private const VANILLA_EVENT_LENGTH = [
        0, 5, 5, 4, 23, 24, 5, 5, 0, 5, 5, 16, 0, 4, 12, 12,
        12, 5, 8, 0, 16, 5, 5, 4, 4, 0, 0, 1, 7, 0, 10, 4,
        5, 2, 0, 4, 4, 104, 64, 0, 4, 5, 10, 0, 5, 4, 12,
    ];
    public const EVENT_RESPONSE_TIME2 = 0x30;
    public const EVENT_CHAT_FINGERPRINT = 0x31;

    // Frame block tags.
    private const BLOCK_END = 0;
    private const BLOCK_SIDE_CHANNEL = 3;
    private const BLOCK_GAME_SPEED = 4;
    private const BLOCK_HOUSE_STATS = 6;
    private const BLOCK_MONEY_IN = 7;
    private const BLOCK_SEAL = 8;

    private const HOUSE_STATS_SAMPLE_SIZE = 84;
    private const MONEY_IN_RECORD_SIZE = 12;
    public const SEAL_RECORD_SIZE = 36;
    public const SEAL_INTERVAL_FRAMES = 900;

    // Side-channel records: chat, beacons and taunts, which travel outside the lockstep.
    public const SIDE_CHANNEL_CHAT = 1;
    private const SIDE_CHANNEL_MAX_PER_FRAME = 64;
    // SideChannelTextLength wide characters, at most three UTF-8 bytes each.
    private const SIDE_CHANNEL_MAX_TEXT_BYTES = 128 * 3;
    public const CHAT_SALT_BYTES = 16;
    public const CHAT_FINGERPRINT_BYTES = 16;

    // HouseStatsFlags
    public const HOUSE_DEFEATED = 1 << 0;
    public const HOUSE_WINNER = 1 << 1;
    public const HOUSE_OBSERVER = 1 << 3;
    public const HOUSE_HUMAN = 1 << 4;
    public const HOUSE_LOST_CONNECTION = 1 << 5;
    public const HOUSE_RESIGNED = 1 << 6;
    public const HOUSE_RECORDING_PLAYER = 1 << 7;

    // What the recorder stored in place of a peer's FRAMEINFO CRC.
    public const FRAMEINFO_MISMATCH = 0;
    public const FRAMEINFO_MATCH = 1;

    private string $path;

    /** @var resource|null */
    private $handle = null;

    private int $fileSize = 0;
    private ?array $header = null;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function __destruct()
    {
        if (is_resource($this->handle))
        {
            fclose($this->handle);
        }
    }

    public static function framesPerSecond(int $gameSpeed): int
    {
        $gameSpeed = max(0, min(self::MAX_GAME_SPEED, $gameSpeed));
        if ($gameSpeed === 0)
        {
            return 60;
        }
        if ($gameSpeed === 1)
        {
            return 45;
        }
        return max(1, intdiv(60, $gameSpeed));
    }

    public function header(): array
    {
        if ($this->header !== null)
        {
            return $this->header;
        }

        $this->open();
        $head = $this->readAt(0, self::HEADER_SIZE);
        if (strlen($head) < self::HEADER_SIZE)
        {
            throw new RuntimeException('File is too small to be a replay');
        }

        if (self::u32($head, 0) !== self::MAGIC)
        {
            throw new RuntimeException('Not a replay file');
        }

        $version = self::u32($head, 4);
        if ($version !== self::VERSION)
        {
            throw new RuntimeException("Unsupported replay format version {$version}");
        }

        $headerSize = self::u32($head, 8);
        $spawnIniSize = self::u32($head, 1032);
        $spawnMapSize = self::u32($head, 1036);
        $gameSpeed = self::u32($head, 1040);
        $spawnIniRawSize = self::u32($head, 1084);
        $spawnMapRawSize = self::u32($head, 1088);

        if ($headerSize < self::HEADER_SIZE || $headerSize > $this->fileSize
            || $spawnIniSize > self::MAX_EMBEDDED_FILE_BYTES || $spawnMapSize > self::MAX_EMBEDDED_FILE_BYTES
            || $spawnIniRawSize > self::MAX_EMBEDDED_FILE_BYTES || $spawnMapRawSize > self::MAX_EMBEDDED_FILE_BYTES
            || ($spawnIniSize === 0) !== ($spawnIniRawSize === 0) || ($spawnMapSize === 0) !== ($spawnMapRawSize === 0)
            || $gameSpeed > self::MAX_GAME_SPEED)
        {
            throw new RuntimeException('Replay header is corrupt');
        }

        $streamOffset = $headerSize + $spawnIniSize + $spawnMapSize;
        if ($streamOffset > $this->fileSize)
        {
            throw new RuntimeException('Replay embedded files run past the end of the file');
        }

        $checkpointOffset = self::u64($head, 1060);
        $checkpointSize = self::u32($head, 1068);
        $statisticsOffset = self::u64($head, 1072);
        $statisticsSize = self::u32($head, 1080);

        // The frame stream runs up to whichever trailing section comes first.
        $streamEnd = $this->fileSize;
        foreach ([[$checkpointOffset, $checkpointSize], [$statisticsOffset, $statisticsSize]] as [$offset, $size])
        {
            if (($offset !== 0 || $size !== 0) && $offset > $streamOffset && $offset <= $this->fileSize)
            {
                $streamEnd = min($streamEnd, $offset);
            }
        }

        return $this->header = [
            'gameMode'          => self::u32($head, 12),
            'uniqueIdCounter'   => self::i32($head, 16),
            'seed'              => self::i32($head, 20),
            'randomNext1'       => self::i32($head, 24),
            'randomNext2'       => self::i32($head, 28),
            'randomizerTable'   => substr($head, 32, 1000),
            'gameSpeed'         => $gameSpeed,
            'recordedUnixTime'  => self::u64($head, 1044),
            'totalFrames'       => self::u32($head, 1052),
            'cleanShutdown'     => (self::u32($head, 1056) & 1) !== 0,
            'headerSize'        => $headerSize,
            'spawnIniSize'      => $spawnIniSize,
            'spawnMapSize'      => $spawnMapSize,
            'spawnIniRawSize'   => $spawnIniRawSize,
            'spawnMapRawSize'   => $spawnMapRawSize,
            'streamOffset'      => $streamOffset,
            'streamEnd'         => $streamEnd,
            'statisticsOffset'  => $statisticsOffset,
            'statisticsSize'    => $statisticsSize,
            'statisticsRawSize' => self::u32($head, 1092),
        ];
    }

    public function spawnIni(): string
    {
        $header = $this->header();
        $text = self::inflateExactly($this->readAt($header['headerSize'], $header['spawnIniSize']), $header['spawnIniRawSize']);
        return str_starts_with($text, "\xEF\xBB\xBF") ? substr($text, 3) : $text;
    }

    /** The embedded spawnmap.ini, or an empty string when the recording carries none (a campaign). */
    public function spawnMap(): string
    {
        $header = $this->header();
        return self::inflateExactly(
            $this->readAt($header['headerSize'] + $header['spawnIniSize'], $header['spawnMapSize']),
            $header['spawnMapRawSize']);
    }

    /**
     * The statistics section the spawner appends as the recording closes: the type table, one
     * summary record per house, the game record, the loaded modules and the canonical seals. Null when
     * the recording has none, which is any that never closed cleanly.
     */
    public function statistics(): ?array
    {
        $header = $this->header();
        $offset = $header['statisticsOffset'];
        $size = $header['statisticsSize'];
        $rawSize = $header['statisticsRawSize'];

        if ($size === 0 || $size > self::MAX_STATISTICS_BYTES || $rawSize === 0 || $rawSize > self::MAX_STATISTICS_BYTES
            || $offset <= $header['streamOffset'] || $offset > $this->fileSize || $size > $this->fileSize - $offset)
        {
            return null;
        }

        try
        {
            $bytes = self::inflateExactly($this->readAt($offset, $size), $rawSize);
        }
        catch (RuntimeException $ex)
        {
            return null;
        }

        $stats = ['types' => [], 'houses' => [], 'game' => null, 'modules' => [], 'seals' => []];

        $pos = 0;
        $length = strlen($bytes);
        while ($pos + 8 <= $length)
        {
            $tag = substr($bytes, $pos, 4);
            $chunkLength = self::u32($bytes, $pos + 4);
            $pos += 8;
            if ($chunkLength > $length - $pos)
            {
                break;
            }

            $chunk = substr($bytes, $pos, $chunkLength);
            $pos += $chunkLength;

            try
            {
                switch ($tag)
                {
                    case 'TYPE':
                        $stats['types'] = $this->parseTypes($chunk);
                        break;
                    case 'HOUS':
                        $stats['houses'] = $this->parseHouses($chunk);
                        break;
                    case 'GAME':
                        $stats['game'] = $this->parseGame($chunk);
                        break;
                    case 'MODS':
                        $stats['modules'] = $this->parseModules($chunk);
                        break;
                    case 'SEAL':
                        $stats['seals'] = $this->parseSeals($chunk);
                        break;
                }
            }
            catch (RuntimeException $ex)
            {
                // A malformed chunk loses only itself; the ones already read are kept.
            }
        }

        return $stats;
    }

    /**
     * Walks the frame stream, calling $onFrame(int $frame, array $record) for every frame record.
     * $record holds 'gameSpeed', 'houseStats', 'moneyIn', 'seals', 'sideChannel' and 'events' when
     * the frame carries them. Each event is unpacked to a 111-byte EventClass string - type, executed flag,
     * house, frame, then the payload, zero beyond what the stream carries - and a FRAMEINFO keeps
     * the recorder's check result where its CRC was.
     *
     * Returns what the walk saw: the last frame number, and whether the stream ended properly.
     */
    public function walkFrames(callable $onFrame, bool $withEvents = true): array
    {
        $header = $this->header();
        [$s, $cutShort] = $this->inflateStream($header['streamOffset'], $header['streamEnd']);
        $length = strlen($s);

        $result = ['lastFrame' => 0, 'sawEnd' => false, 'truncated' => $cutShort, 'stoppedEarly' => false];
        $previousFrame = -1;
        $p = 0;

        while ($p < $length)
        {
            $start = $p;
            $head = self::varint($s, $p, $length);
            if ($head === null)
            {
                $result['truncated'] = true;
                break;
            }

            if ($head === 0)
            {
                $result['sawEnd'] = true;
                break;
            }

            $delta = $head >> 1;
            if ($delta === 0)
            {
                $result['stoppedEarly'] = true;
                break;
            }
            $frame = $previousFrame + $delta;

            $record = [];
            if ($head & 1)
            {
                if ($p + 2 > $length) { $result['truncated'] = true; break; }
                $record['crc'] = self::u16($s, $p);
                $p += 2;
            }

            $eventCount = self::varint($s, $p, $length);
            if ($eventCount === null || $eventCount > self::MAX_EVENTS_PER_FRAME)
            {
                $result[$eventCount === null ? 'truncated' : 'stoppedEarly'] = true;
                break;
            }

            if (!$this->readBlocks($s, $p, $length, $record))
            {
                $result['truncated'] = true;
                break;
            }

            $events = [];
            $ok = true;
            for ($i = 0; $i < $eventCount; $i++)
            {
                $event = $this->readEvent($s, $p, $length, $frame);
                if ($event === null)
                {
                    $ok = false;
                    break;
                }
                if ($withEvents)
                {
                    $events[] = $event;
                }
            }
            if (!$ok)
            {
                $result['truncated'] = true;
                break;
            }
            if (count($events) > 0)
            {
                $record['events'] = $events;
            }

            $previousFrame = $frame;
            $result['lastFrame'] = $frame;
            $onFrame($frame, $record);
        }

        return $result;
    }

    /** The tagged blocks of one record, up to and including the end tag. False when the stream ends first. */
    private function readBlocks(string $s, int &$p, int $length, array &$record): bool
    {
        while (true)
        {
            $tag = self::varint($s, $p, $length);
            if ($tag === null)
            {
                return false;
            }
            if ($tag === self::BLOCK_END)
            {
                return true;
            }

            $blockLength = self::varint($s, $p, $length);
            if ($blockLength === null || $blockLength > self::MAX_BLOCK_BYTES || $p + $blockLength > $length)
            {
                return false;
            }

            $b = substr($s, $p, $blockLength);
            $p += $blockLength;
            $q = 0;

            switch ($tag)
            {
                case self::BLOCK_GAME_SPEED:
                    if ($blockLength >= 1 && ord($b[0]) <= self::MAX_GAME_SPEED)
                    {
                        $record['gameSpeed'] = ord($b[0]);
                    }
                    break;

                case self::BLOCK_HOUSE_STATS:
                    $count = self::varint($b, $q, $blockLength);
                    if ($count !== null && $count >= 1 && $count <= 32 && $q + $count * self::HOUSE_STATS_SAMPLE_SIZE <= $blockLength)
                    {
                        $samples = [];
                        for ($i = 0; $i < $count; $i++)
                        {
                            $samples[] = self::parseHouseStats($b, $q + $i * self::HOUSE_STATS_SAMPLE_SIZE);
                        }
                        $record['houseStats'] = $samples;
                    }
                    break;

                case self::BLOCK_MONEY_IN:
                    $count = self::varint($b, $q, $blockLength);
                    if ($count !== null && $count >= 1 && $count <= 1024 && $q + $count * self::MONEY_IN_RECORD_SIZE <= $blockLength)
                    {
                        $payments = [];
                        for ($i = 0; $i < $count; $i++)
                        {
                            $o = $q + $i * self::MONEY_IN_RECORD_SIZE;
                            $payments[] = [
                                'house'  => ord($b[$o]),
                                'caller' => self::u32($b, $o + 4),
                                'amount' => self::i32($b, $o + 8),
                            ];
                        }
                        $record['moneyIn'] = $payments;
                    }
                    break;

                case self::BLOCK_SEAL:
                    if ($blockLength === self::SEAL_RECORD_SIZE)
                    {
                        $record['seals'][self::i32($b, 0)] = bin2hex(substr($b, 4, 32));
                    }
                    break;

                case self::BLOCK_SIDE_CHANNEL:
                    $sideChannel = self::parseSideChannel($b);
                    if ($sideChannel !== null)
                    {
                        $record['sideChannel'] = $sideChannel;
                    }
                    break;

                // The camera, selection and selection triggers are the recording player's own view;
                // nothing on the ladder uses them, and an unknown tag is skipped the same way.
            }
        }
    }

    /**
     * A SideChannel block: chat, beacons and taunts as this recording saw them. Null when the block is
     * malformed, which loses only that frame's records.
     *
     * Each record is uint8 type, zigzag house, aux (a chat line's colour scheme), X, Y and Z, then the
     * UTF-8 sender name and text, each behind a varint length. A chat line then has a flags byte: bit 0
     * is followed by the 16-byte salt of its fingerprint, bit 1 by a uint8 label and a uint8 mask of
     * the houses it went to.
     */
    private static function parseSideChannel(string $b): ?array
    {
        $length = strlen($b);
        $p = 0;
        $count = self::varint($b, $p, $length);
        if ($count === null || $count > self::SIDE_CHANNEL_MAX_PER_FRAME)
        {
            return null;
        }

        $records = [];
        for ($i = 0; $i < $count; $i++)
        {
            if ($p >= $length)
            {
                return null;
            }
            $type = ord($b[$p++]);

            $numbers = [];
            foreach (['house', 'aux', 'x', 'y', 'z'] as $field)
            {
                $raw = self::varint($b, $p, $length);
                if ($raw === null)
                {
                    return null;
                }
                $numbers[$field] = ($raw >> 1) ^ -($raw & 1);
            }

            $name = self::utf8Field($b, $p, $length);
            $text = $name === null ? null : self::utf8Field($b, $p, $length);
            if ($text === null)
            {
                return null;
            }

            $record = ['type' => $type, 'house' => $numbers['house'], 'aux' => $numbers['aux'],
                'name' => $name, 'text' => $text, 'salt' => null, 'label' => 0, 'recipients' => 0];

            if ($type === self::SIDE_CHANNEL_CHAT)
            {
                if ($p >= $length)
                {
                    return null;
                }
                $flags = ord($b[$p++]);
                if (($flags & ~3) !== 0)
                {
                    return null;
                }
                if ($flags & 1)
                {
                    if ($p + self::CHAT_SALT_BYTES > $length)
                    {
                        return null;
                    }
                    $record['salt'] = substr($b, $p, self::CHAT_SALT_BYTES);
                    $p += self::CHAT_SALT_BYTES;
                }
                if ($flags & 2)
                {
                    if ($p + 2 > $length)
                    {
                        return null;
                    }
                    $record['label'] = ord($b[$p]);
                    $record['recipients'] = ord($b[$p + 1]);
                    $p += 2;
                }
            }

            $records[] = $record;
        }

        return $records;
    }

    private static function utf8Field(string $b, int &$p, int $length): ?string
    {
        $size = self::varint($b, $p, $length);
        if ($size === null || $size > self::SIDE_CHANNEL_MAX_TEXT_BYTES || $p + $size > $length)
        {
            return null;
        }
        $text = substr($b, $p, $size);
        $p += $size;
        return mb_scrub($text, 'UTF-8');
    }

    /** One event as a 111-byte EventClass string, or null when the stream ends part-way through. */
    private function readEvent(string $s, int &$p, int $length, int $frame): ?string
    {
        if ($p + 2 > $length)
        {
            return null;
        }
        $type = ord($s[$p]);
        $house = $s[$p + 1];
        $p += 2;

        $delta = self::varint($s, $p, $length);
        if ($delta === null)
        {
            return null;
        }
        $eventFrame = $frame + (($delta >> 1) ^ -($delta & 1));

        if ($type === self::EVENT_FRAMEINFO)
        {
            if ($p + 4 > $length)
            {
                return null;
            }
            // CommandCount, Delay, then the check result, which goes where the CRC was.
            $payload = $s[$p + 3] . "\0\0\0" . substr($s, $p, 3);
            $p += 4;
        }
        else
        {
            $payloadLength = self::payloadLength($type);
            if ($payloadLength < 0)
            {
                $payloadLength = self::varint($s, $p, $length);
                if ($payloadLength === null || $payloadLength > 104)
                {
                    return null;
                }
            }
            if ($p + $payloadLength > $length)
            {
                return null;
            }
            $payload = substr($s, $p, $payloadLength);
            $p += $payloadLength;
        }

        return chr($type) . "\0" . $house . pack('V', $eventFrame & 0xFFFFFFFF)
            . str_pad($payload, 104, "\0");
    }

    public static function payloadLength(int $type): int
    {
        if ($type < count(self::VANILLA_EVENT_LENGTH))
        {
            return self::VANILLA_EVENT_LENGTH[$type];
        }
        return match ($type)
        {
            self::EVENT_RESPONSE_TIME2 => 2,
            self::EVENT_CHAT_FINGERPRINT => 16,
            default => -1,
        };
    }

    /**
     * The whole frame stream, inflated. Returns [bytes, cutShort]: a recording that died with the
     * game ends part-way through a deflate block, and everything before that still stands.
     */
    private function inflateStream(int $start, int $end): array
    {
        $inflate = inflate_init(ZLIB_ENCODING_RAW);
        fseek($this->handle, $start);
        $remaining = max(0, $end - $start);
        $out = '';
        $cutShort = false;

        while ($remaining > 0 && strlen($out) <= self::MAX_STREAM_BYTES)
        {
            $chunk = fread($this->handle, min(65536, $remaining));
            if ($chunk === false || $chunk === '')
            {
                break;
            }
            $remaining -= strlen($chunk);

            $inflated = @inflate_add($inflate, $chunk, ZLIB_SYNC_FLUSH);
            if ($inflated === false)
            {
                $cutShort = true;
                break;
            }
            $out .= $inflated;

            if (inflate_get_status($inflate) === ZLIB_STREAM_END)
            {
                break;
            }
        }

        return [$out, $cutShort];
    }

    private static function inflateExactly(string $stored, int $rawSize): string
    {
        if ($stored === '')
        {
            return '';
        }

        $raw = @gzinflate($stored, $rawSize);
        if ($raw === false || strlen($raw) !== $rawSize)
        {
            throw new RuntimeException('An embedded part of the replay does not inflate to the size its header gives');
        }
        return $raw;
    }

    private static function parseHouseStats(string $bytes, int $o): array
    {
        $f = array_values(unpack('V21', $bytes, $o));
        foreach ($f as $k => $v)
        {
            if ($k < 20 && $v >= 0x80000000)
            {
                $f[$k] = $v - 0x100000000;
            }
        }

        return [
            'house'           => $f[0],
            'credits'         => $f[1],
            'storedOre'       => $f[2],
            'creditsSpent'    => $f[3],
            'harvested'       => $f[4],
            'powerOutput'     => $f[5],
            'powerDrain'      => $f[6],
            'units'           => $f[7],
            'infantry'        => $f[8],
            'aircraft'        => $f[9],
            'buildings'       => $f[10],
            'armyValue'       => $f[11],
            'buildingValue'   => $f[12],
            'unitsKilled'     => $f[13],
            'buildingsKilled' => $f[14],
            'unitsLost'       => $f[15],
            'buildingsLost'   => $f[16],
            'unitsBuilt'      => $f[17],
            'buildingsBuilt'  => $f[18],
            'score'           => $f[19],
            'flags'           => $f[20],
        ];
    }

    private function parseTypes(string $c): array
    {
        $pos = 0;
        $lists = self::takeU32($c, $pos);
        if ($lists > 16)
        {
            throw new RuntimeException('type list count');
        }

        $types = [];
        for ($l = 0; $l < $lists; $l++)
        {
            $kind = self::takeU32($c, $pos);
            $count = self::takeU32($c, $pos);
            if ($count > 8192)
            {
                throw new RuntimeException('type count');
            }

            $list = [];
            for ($i = 0; $i < $count; $i++)
            {
                $id = self::fixedAscii(self::take($c, $pos, 0x18));
                $cameo = self::fixedAscii(self::take($c, $pos, 0x19));
                $nameLength = self::u16(self::take($c, $pos, 2), 0);
                if ($nameLength > 1024)
                {
                    throw new RuntimeException('type name');
                }
                $name = self::utf16(self::take($c, $pos, $nameLength * 2));
                $cost = self::i32(self::take($c, $pos, 4), 0);
                $flags = self::takeU32($c, $pos);

                $list[] = ['id' => $id, 'cameo' => $cameo, 'name' => $name, 'cost' => $cost, 'flags' => $flags];
            }
            $types[$kind] = $list;
        }

        return $types;
    }

    private function parseHouses(string $c): array
    {
        $pos = 0;
        $count = self::takeU32($c, $pos);
        if ($count > 64)
        {
            throw new RuntimeException('house count');
        }

        $houses = [];
        for ($h = 0; $h < $count; $h++)
        {
            $r = self::take($c, $pos, 282);
            $arrayCount = self::takeU32($c, $pos);
            if ($arrayCount > 64)
            {
                throw new RuntimeException('array count');
            }

            $arrays = [];
            for ($a = 0; $a < $arrayCount; $a++)
            {
                $n = self::takeU32($c, $pos);
                if ($n > 0x1000)
                {
                    throw new RuntimeException('array length');
                }
                $arrays[] = $n > 0 ? self::signedList(self::take($c, $pos, $n * 4), $n) : [];
            }

            $houses[] = [
                'house'                  => self::i32($r, 0),
                'name'                   => self::utf16(substr($r, 4, 42)),
                'country'                => self::fixedAscii(substr($r, 46, 24)),
                'color'                  => self::i32($r, 70),
                'spawn'                  => self::i32($r, 74),
                'allies'                 => self::u32($r, 78),
                'flags'                  => self::u32($r, 82),
                'credits'                => self::i32($r, 86),
                'storedOre'              => self::i32($r, 90),
                'creditsSpent'           => self::i32($r, 94),
                'harvested'              => self::i32($r, 98),
                'powerOutput'            => self::i32($r, 102),
                'powerDrain'             => self::i32($r, 106),
                'unitsLost'              => self::i32($r, 110),
                'buildingsLost'          => self::i32($r, 114),
                'score'                  => self::i32($r, 118),
                'unitsKilledOfHouse'     => self::signedList(substr($r, 122, 80), 20),
                'buildingsKilledOfHouse' => self::signedList(substr($r, 202, 80), 20),
                'arrays'                 => $arrays,
            ];
        }

        return $houses;
    }

    private function parseGame(string $c): array
    {
        if (strlen($c) < 20)
        {
            throw new RuntimeException('game record');
        }

        return [
            'endUnixTime'    => self::u64($c, 0),
            'endFrame'       => self::i32($c, 8),
            'outOfSyncFrame' => self::i32($c, 12),
            'outOfSync'      => ord($c[16]) !== 0,
            'sawCompletion'  => ord($c[17]) !== 0,
        ];
    }

    private function parseModules(string $c): array
    {
        $pos = 0;
        $count = self::takeU32($c, $pos);
        if ($count > 4096)
        {
            throw new RuntimeException('module count');
        }

        $modules = [];
        for ($i = 0; $i < $count; $i++)
        {
            $base = self::takeU32($c, $pos);
            $size = self::takeU32($c, $pos);
            $stamp = self::takeU32($c, $pos);
            $nameLength = self::u16(self::take($c, $pos, 2), 0);
            if ($nameLength > 260)
            {
                throw new RuntimeException('module name');
            }
            $name = self::utf16(self::take($c, $pos, $nameLength * 2));
            $modules[] = ['name' => $name, 'base' => $base, 'size' => $size, 'stamp' => $stamp];
        }

        return $modules;
    }

    /** [throughFrame => hex hash], in the order written - the last one over whatever followed the final full interval. */
    private function parseSeals(string $c): array
    {
        $pos = 0;
        $count = self::takeU32($c, $pos);
        if ($count > 1000000)
        {
            throw new RuntimeException('seal count');
        }

        $seals = [];
        for ($i = 0; $i < $count; $i++)
        {
            $record = self::take($c, $pos, self::SEAL_RECORD_SIZE);
            $seals[self::i32($record, 0)] = bin2hex(substr($record, 4, 32));
        }
        return $seals;
    }

    private function open(): void
    {
        if (is_resource($this->handle))
        {
            return;
        }

        $handle = @fopen($this->path, 'rb');
        if ($handle === false)
        {
            throw new RuntimeException("Cannot open replay {$this->path}");
        }

        $this->handle = $handle;
        $this->fileSize = (int) fstat($handle)['size'];
    }

    private function readAt(int $offset, int $length): string
    {
        if ($length <= 0)
        {
            return '';
        }

        fseek($this->handle, $offset);
        $data = '';
        while (strlen($data) < $length && !feof($this->handle))
        {
            $chunk = fread($this->handle, $length - strlen($data));
            if ($chunk === false || $chunk === '')
            {
                break;
            }
            $data .= $chunk;
        }

        return $data;
    }

    /** LEB128, as the spawner writes it; null when the bytes run out first. */
    private static function varint(string $b, int &$p, int $length): ?int
    {
        $value = 0;
        for ($shift = 0; $shift < 35; $shift += 7)
        {
            if ($p >= $length)
            {
                return null;
            }
            $byte = ord($b[$p++]);
            $value |= ($byte & 0x7F) << $shift;
            if (($byte & 0x80) === 0)
            {
                return $value;
            }
        }
        return null;
    }

    public static function u16(string $b, int $o): int
    {
        return unpack('v', $b, $o)[1];
    }

    public static function u32(string $b, int $o): int
    {
        return unpack('V', $b, $o)[1];
    }

    public static function i32(string $b, int $o): int
    {
        $v = unpack('V', $b, $o)[1];
        return $v >= 0x80000000 ? $v - 0x100000000 : $v;
    }

    public static function u64(string $b, int $o): int
    {
        return unpack('P', $b, $o)[1];
    }

    private static function signedList(string $b, int $n): array
    {
        $values = array_values(unpack("V{$n}", $b));
        foreach ($values as $k => $v)
        {
            if ($v >= 0x80000000)
            {
                $values[$k] = $v - 0x100000000;
            }
        }
        return $values;
    }

    private static function take(string $b, int &$pos, int $length): string
    {
        if ($length < 0 || $pos + $length > strlen($b))
        {
            throw new RuntimeException('chunk ended early');
        }
        $data = substr($b, $pos, $length);
        $pos += $length;
        return $data;
    }

    private static function takeU32(string $b, int &$pos): int
    {
        return self::u32(self::take($b, $pos, 4), 0);
    }

    private static function fixedAscii(string $b): string
    {
        $end = strpos($b, "\0");
        return $end === false ? $b : substr($b, 0, $end);
    }

    private static function utf16(string $b): string
    {
        for ($i = 0; $i + 1 < strlen($b); $i += 2)
        {
            if ($b[$i] === "\0" && $b[$i + 1] === "\0")
            {
                $b = substr($b, 0, $i);
                break;
            }
        }
        return mb_convert_encoding($b, 'UTF-8', 'UTF-16LE');
    }
}
