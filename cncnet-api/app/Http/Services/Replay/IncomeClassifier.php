<?php

namespace App\Http\Services\Replay;

/**
 * Names where a payment into a house came from, by the return address of the code that paid it.
 *
 * The spawner records every call to HouseClass::TransactMoney with its caller. Game addresses are
 * fixed; Ares and Phobos are matched by module name and build timestamp, since a different build
 * moves them. Ported from yrrp-analyser's IncomeClassifier.
 */
class IncomeClassifier
{
    public const HARVESTED = 'Harvested';
    public const BUILDINGS = 'Buildings';
    public const SOLD = 'Sold';
    public const REFUNDED = 'Refunded';
    public const STARTING_CREDITS = 'StartingCredits';
    public const GRINDING = 'Grinding';
    public const CRATES = 'Crates';
    public const STOLEN = 'Stolen';
    public const BOUNTY = 'Bounty';
    public const SUPERWEAPON = 'Superweapon';
    public const WARHEAD = 'Warhead';
    public const OTHER = 'Other';
    public const UNCLASSIFIED = 'Unclassified';

    private const GAME = 'game';
    private const GAME_IMAGE_BASE = 0x400000;
    private const GAME_IMAGE_END = 0x400000 + 0x793000;

    private const ARES_30P1 = 0x61DAA114;
    private const PHOBOS_BUILD = 0x697A04F6;

    /** [module, build timestamp (0 = any), offset, source] */
    private const RULES = [
        [self::GAME, 0, 0x4CA04B, self::REFUNDED],          // FactoryClass::Abandon
        [self::GAME, 0, 0x446E94, self::REFUNDED],          // BuildingClass::Grand_Opening - free unit not placed
        [self::GAME, 0, 0x446EE2, self::REFUNDED],
        [self::GAME, 0, 0x44A17B, self::SOLD],              // BuildingClass::Mission_Deconstruction
        [self::GAME, 0, 0x44A1B5, self::SOLD],
        [self::GAME, 0, 0x44A227, self::SOLD],
        [self::GAME, 0, 0x44AAF4, self::SOLD],
        [self::GAME, 0, 0x4575E9, self::SOLD],              // BuildingClass_Sell_Upgrade
        [self::GAME, 0, 0x4D9FCB, self::SOLD],              // FootClass::Sell_Back
        [self::GAME, 0, 0x73A0BC, self::GRINDING],          // UnitClass::Per_Cell_Process
        [self::GAME, 0, 0x73A100, self::GRINDING],
        [self::GAME, 0, 0x73A12A, self::GRINDING],
        [self::GAME, 0, 0x73A15C, self::GRINDING],
        [self::GAME, 0, 0x519880, self::GRINDING],          // InfantryClass::Per_Cell_Process
        [self::GAME, 0, 0x4587B6, self::BUILDINGS],         // BuildingClass::Produce_Cash - oil derrick
        [self::GAME, 0, 0x43FDC6, self::BUILDINGS],         // BuildingClass::AI - cash-producing building
        [self::GAME, 0, 0x4482D5, self::BUILDINGS],         // BuildingClass::Captured - capture bonus
        [self::GAME, 0, 0x4824D9, self::CRATES],            // CellClass::Goodie_Check - money crate
        [self::GAME, 0, 0x457460, self::STOLEN],            // BuildingClass_Infiltrate - spy in a refinery
        [self::GAME, 0, 0x6FA1C5, self::STOLEN],            // TechnoClass::AI - money drain
        [self::GAME, 0, 0x449337, self::OTHER],             // BuildingClass::Captured - upgrade refund
        [self::GAME, 0, 0x4C6278, self::OTHER],             // EvadeClass::Do
        [self::GAME, 0, 0x5D6FA1, self::OTHER],             // Generate_Units - unplaceable starting unit
        [self::GAME, 0, 0x686A78, self::STARTING_CREDITS],  // Read_Scenario_INI - AI starting credits bonus

        ['Ares.dll', self::ARES_30P1, 0x44DCE, self::HARVESTED],   // UnitClass_Mi_Unload_Storage
        ['Ares.dll', self::ARES_30P1, 0x46E3E, self::HARVESTED],   // InfantryClass_Slave_UnloadAt_Storage
        ['Ares.dll', self::ARES_30P1, 0x14D75, self::BUILDINGS],   // BuildingClass_Update_ProduceCash
        ['Ares.dll', self::ARES_30P1, 0x1438F, self::BUILDINGS],   // BuildingClass_ChangeOwnership_ProduceCash
        ['Ares.dll', self::ARES_30P1, 0x12A8D, self::STOLEN],      // BuildingClass_Infiltrate
        ['Ares.dll', self::ARES_30P1, 0x43E1F, self::BOUNTY],      // TechnoClass_RegisterDestruction_Bounty
        ['Ares.dll', self::ARES_30P1, 0x33A11, self::SUPERWEAPON], // SuperClass_Launch - Money.Amount
        ['Ares.dll', self::ARES_30P1, 0x36404, self::SUPERWEAPON], // SuperClass_Update_DrainMoney
        ['Ares.dll', self::ARES_30P1, 0x4D185, self::GRINDING],    // UnitClass_UpdatePosition_EnteredGrinder

        ['Phobos.dll', self::PHOBOS_BUILD, 0x63A5E, self::SOLD],   // FootClass_Sell
        ['Phobos.dll', self::PHOBOS_BUILD, 0x7D71D, self::WARHEAD], // TransactMoney warhead
        ['Phobos.dll', self::PHOBOS_BUILD, 0x4E929, self::OTHER],  // PassengerDeletion.Soylent refund
    ];

    private array $modules;
    private array $cache = [];

    public function __construct(array $modules)
    {
        $this->modules = $modules;
    }

    /** Whether money from this source counts as earned rather than spending coming back. */
    public static function isIncome(string $source): bool
    {
        return $source !== self::REFUNDED && $source !== self::STARTING_CREDITS;
    }

    public function classify(int $caller): string
    {
        if (isset($this->cache[$caller]))
        {
            return $this->cache[$caller];
        }

        [$module, $offset, $stamp] = $this->resolve($caller);

        $source = self::UNCLASSIFIED;
        foreach (self::RULES as [$ruleModule, $ruleStamp, $ruleOffset, $ruleSource])
        {
            if ($ruleOffset !== $offset || strcasecmp($ruleModule, $module) !== 0)
            {
                continue;
            }
            if ($ruleStamp !== 0 && $stamp !== 0 && $ruleStamp !== $stamp)
            {
                continue;
            }
            $source = $ruleSource;
            break;
        }

        return $this->cache[$caller] = $source;
    }

    /** [module, offset within it, build timestamp] */
    private function resolve(int $caller): array
    {
        foreach ($this->modules as $module)
        {
            if ($caller < $module['base'] || $caller - $module['base'] >= $module['size'])
            {
                continue;
            }

            return $module['base'] === self::GAME_IMAGE_BASE
                ? [self::GAME, $caller, $module['stamp']]
                : [$module['name'], $caller - $module['base'], $module['stamp']];
        }

        if (count($this->modules) === 0 && $caller >= self::GAME_IMAGE_BASE && $caller < self::GAME_IMAGE_END)
        {
            return [self::GAME, $caller, 0];
        }

        return ['?', $caller, 0];
    }
}
