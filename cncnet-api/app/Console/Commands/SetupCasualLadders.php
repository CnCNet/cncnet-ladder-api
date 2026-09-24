<?php

namespace App\Console\Commands;

use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Map;
use App\Models\MapPool;
use App\Models\QmLadderRules;
use App\Models\QmMap;
use App\Models\Side;
use App\Models\SpawnOption;
use App\Models\SpawnOptionString;
use App\Models\SpawnOptionType;
use App\Models\SpawnOptionValue;
use Illuminate\Console\Command;

/**
 * Creates the ladders used by the casual matchmaking client in a local development environment.
 * In production, casual ladders are created and configured through the ladder admin pages.
 */
class SetupCasualLadders extends Command
{
    protected $signature = 'qm:setup-casual-ladders';

    protected $description = 'Create casual matchmaking ladders, map pools and spawn options for local development';

    /**
     * Ladders expected by the default client Matchmaking.ini. The key is the client mode id.
     */
    private const LADDERS = [
        '1v1' => ['abbreviation' => 'yr-casual-1v1', 'name' => 'Casual 1v1', 'player_count' => 2],
        '2v2' => ['abbreviation' => 'yr-casual-2v2', 'name' => 'Casual 2v2', 'player_count' => 4],
        '3v3' => ['abbreviation' => 'yr-casual-3v3', 'name' => 'Casual 3v3', 'player_count' => 6],
        '4v4' => ['abbreviation' => 'yr-casual-4v4', 'name' => 'Casual 4v4', 'player_count' => 8],
    ];

    /**
     * Map pools of the development ladders. Clients show the map pool of a ladder from the ladder API,
     * and the maps must be installed with the game.
     */
    private const MAPS = [
        '1v1' => [
            ['hash' => 'C1C9FC820EC9FBB4932D2FAFECA317B9D889D839', 'name' => 'A Hill Between', 'filename' => 'hillbtwn.map'],
            ['hash' => '9403314549EBCE94AA37FC5B2A8489D289016998', 'name' => 'Fjord', 'filename' => '2_fjord.map'],
        ],
        '2v2' => [
            ['hash' => '7E19FFFB5A97EF5CD0105C18EBD28BC1FE012616', 'name' => 'Depth Charge', 'filename' => 'xmp10s4.map'],
            ['hash' => 'B02D57D2A0F81AE6D177E5FED08921CFE3F5D3A1', 'name' => 'Invasion Confirmed', 'filename' => 'xinvasion.map'],
        ],
        '3v3' => [
            ['hash' => '68D761CA4CE5F9C23D025429C2CE4C6486A6DD24', 'name' => 'Crushed Ice', 'filename' => '6_crushed_ice.map'],
            ['hash' => '5C26931A87068E64CD8E1E6DD245350F033C9274', 'name' => 'East vs Best', 'filename' => 'EastVsBest.map'],
        ],
        '4v4' => [
            ['hash' => '5CDACEBE54B195BB99CDE445DB0B338CB36C0B05', 'name' => 'Grand Crevice', 'filename' => '8grandcrevice12.map'],
            ['hash' => '18345899A6EF5D8FC53B48869136B74CFD42B741', 'name' => 'Boiling Point', 'filename' => '8boilingpoint1.map'],
        ],
    ];

    /**
     * Game rules written to [Settings] in spawn.ini through the ladder spawn options.
     */
    private const SPAWN_SETTINGS = [
        'Credits' => '10000',
        'UnitCount' => '0',
        'GameSpeed' => '1',
        'ShortGame' => 'Yes',
        'MCVRedeploy' => 'Yes',
        'MultiEngineer' => 'No',
        'BridgeDestroy' => 'Yes',
        'BuildOffAlly' => 'Yes',
    ];

    public function handle(): int
    {
        if (!app()->environment(['local', 'testing']))
        {
            $this->error('This command only creates development ladders and can only run in the local or testing environment.');

            return self::FAILURE;
        }

        $this->ensureSpawnIniOptionType();

        foreach (self::LADDERS as $modeId => $config)
        {
            $maps = self::MAPS[$modeId];
            $ladder = $this->setupLadder($config, $maps);

            $this->line("Ladder {$ladder->abbreviation}: {$config['player_count']} players, " . count($maps) . " maps");
        }

        return self::SUCCESS;
    }

    private function setupLadder(array $config, array $maps): Ladder
    {
        $ladder = Ladder::firstOrNew(['abbreviation' => $config['abbreviation']]);
        $ladder->abbreviation = $config['abbreviation'];
        $ladder->name = $config['name'];
        $ladder->game = 'yr';
        $ladder->clans_allowed = false;
        $ladder->ladder_type = $config['player_count'] > 2 ? Ladder::TWO_VS_TWO : Ladder::ONE_VS_ONE;
        $ladder->is_casual = true;
        $ladder->save();

        foreach ([0 => 'Allied', 1 => 'Soviet', 2 => 'Yuri'] as $localId => $sideName)
        {
            Side::firstOrCreate(['ladder_id' => $ladder->id, 'local_id' => $localId], ['name' => $sideName]);
        }

        $rules = QmLadderRules::firstOrNew(['ladder_id' => $ladder->id]);
        $rules->ladder_id = $ladder->id;
        $rules->player_count = $config['player_count'];
        // Read the raw attribute: QmLadderRules also has an allowed_sides() method
        $allowedSides = $rules->getAttributes()['allowed_sides'] ?? '-1,0,1,2';
        $rules->allowed_sides = $allowedSides;
        $rules->save();

        $mapPool = MapPool::firstOrCreate(['ladder_id' => $ladder->id]);
        $ladder->map_pool_id = $mapPool->id;
        $ladder->save();

        foreach ($maps as $bitIndex => $mapConfig)
        {
            $map = Map::firstOrNew(['hash' => $mapConfig['hash'], 'ladder_id' => $ladder->id]);
            $map->hash = $mapConfig['hash'];
            $map->ladder_id = $ladder->id;
            $map->name = $mapConfig['name'];
            $map->filename = $mapConfig['filename'];
            $map->spawn_count = $config['player_count'];
            $map->save();

            $qmMap = QmMap::firstOrCreate(
                ['ladder_id' => $ladder->id, 'map_pool_id' => $mapPool->id, 'map_id' => $map->id],
                [
                    'description' => $mapConfig['name'],
                    'valid' => 1,
                    'bit_idx' => $bitIndex,
                    'allowed_sides' => $allowedSides,
                ]
            );

            // Players get random start locations; team matches require this or explicit team spawn orders
            $qmMap->spawn_order = '0,0';
            $qmMap->random_spawns = true;
            $qmMap->save();
        }

        foreach (self::SPAWN_SETTINGS as $key => $value)
        {
            $this->setLadderSpawnOption($ladder, 'Settings', $key, $value);
        }

        $history = LadderHistory::where('ladder_id', $ladder->id)
            ->where('starts', now()->startOfMonth())
            ->first() ?? new LadderHistory();
        $history->ladder_id = $ladder->id;
        $history->starts = now()->startOfMonth();
        $history->ends = now()->endOfMonth();
        $history->short = now()->month . '-' . now()->year;
        $history->save();

        return $ladder;
    }

    /**
     * Production databases contain the spawn option types, but a database created
     * from the schema dump does not, which breaks spawn.ini generation for ladder options.
     */
    private function ensureSpawnIniOptionType(): void
    {
        if (SpawnOptionType::find(SpawnOptionType::SPAWN_INI) !== null)
            return;

        $type = new SpawnOptionType('SPAWN_INI');
        $type->id = SpawnOptionType::SPAWN_INI;
        $type->save();
    }

    private function setLadderSpawnOption(Ladder $ladder, string $section, string $key, string $value): void
    {
        $sectionId = SpawnOptionString::findOrCreate($section)->id;
        $keyId = SpawnOptionString::findOrCreate($key)->id;

        $spawnOption = SpawnOption::where('type_id', SpawnOptionType::SPAWN_INI)
            ->where('string1_id', $sectionId)
            ->where('string2_id', $keyId)
            ->first();

        if ($spawnOption === null)
        {
            $spawnOption = SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "{$section}.{$key}", $section, $key);
            $spawnOption->save();
        }

        $spawnOptionValue = SpawnOptionValue::where('ladder_id', $ladder->id)
            ->whereNull('qm_map_id')
            ->where('spawn_option_id', $spawnOption->id)
            ->first() ?? new SpawnOptionValue();

        $spawnOptionValue->ladder_id = $ladder->id;
        $spawnOptionValue->spawn_option_id = $spawnOption->id;
        $spawnOptionValue->value_id = SpawnOptionString::findOrCreate($value)->id;
        $spawnOptionValue->save();
    }
}
