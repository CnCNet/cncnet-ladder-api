<?php

namespace Tests\Unit\Models;

use App\Models\QmMapPick;
use App\Models\Ladder;
use App\Models\Side;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QmMapPickTest extends TestCase
{
    use RefreshDatabase;

    // side_index encoding: -3 unset (main faction), -2 veto, -1 random, 0+ side local_id.

    public function test_is_veto_returns_true_for_minus_two(): void
    {
        $pick = new QmMapPick();
        $pick->side_index = -2;

        $this->assertTrue($pick->isVeto());
    }

    public function test_is_veto_returns_false_for_other_values(): void
    {
        $pick = new QmMapPick();

        foreach ([-3, -1, 0, 5] as $value) {
            $pick->side_index = $value;
            $this->assertFalse($pick->isVeto(), "side_index {$value} must not be a veto");
        }
    }

    public function test_is_random_returns_true_for_minus_one(): void
    {
        $pick = new QmMapPick();
        $pick->side_index = -1;

        $this->assertTrue($pick->isRandom());
    }

    public function test_is_random_returns_false_for_other_values(): void
    {
        $pick = new QmMapPick();

        foreach ([-3, -2, 0, 5] as $value) {
            $pick->side_index = $value;
            $this->assertFalse($pick->isRandom(), "side_index {$value} must not be random");
        }
    }

    public function test_is_unset_returns_true_only_for_minus_three(): void
    {
        $pick = new QmMapPick();

        $pick->side_index = -3;
        $this->assertTrue($pick->isUnset());

        foreach ([-2, -1, 0] as $value) {
            $pick->side_index = $value;
            $this->assertFalse($pick->isUnset(), "side_index {$value} must not be unset");
        }
    }

    public function test_validate_side_index_accepts_special_values(): void
    {
        $ladder = new Ladder();
        $ladder->id = 1;

        $this->assertTrue(QmMapPick::validateSideIndex(-3, $ladder)); // unset
        $this->assertTrue(QmMapPick::validateSideIndex(-2, $ladder)); // veto
        $this->assertTrue(QmMapPick::validateSideIndex(-1, $ladder)); // random
    }

    /**
     * Test that validateSideIndex() returns true for valid faction indices.
     */
    public function test_validate_side_index_accepts_valid_factions(): void
    {
        // Create ladder with sides 0-9
        $ladder = Ladder::factory()->create();

        for ($i = 0; $i <= 9; $i++) {
            Side::factory()->create([
                'ladder_id' => $ladder->id,
                'local_id' => $i,
            ]);
        }

        // Test valid side indices
        $this->assertTrue(QmMapPick::validateSideIndex(0, $ladder));
        $this->assertTrue(QmMapPick::validateSideIndex(5, $ladder));
        $this->assertTrue(QmMapPick::validateSideIndex(9, $ladder));
    }

    /**
     * Test that validateSideIndex() returns false for out-of-range faction indices.
     */
    public function test_validate_side_index_rejects_out_of_range_factions(): void
    {
        // Create ladder with sides 0-9
        $ladder = Ladder::factory()->create();

        for ($i = 0; $i <= 9; $i++) {
            Side::factory()->create([
                'ladder_id' => $ladder->id,
                'local_id' => $i,
            ]);
        }

        // Test invalid side indices
        $this->assertFalse(QmMapPick::validateSideIndex(10, $ladder));
        $this->assertFalse(QmMapPick::validateSideIndex(99, $ladder));
        $this->assertFalse(QmMapPick::validateSideIndex(-4, $ladder));
    }

    /**
     * Test that side_index is cast to integer.
     */
    public function test_side_index_is_cast_to_integer(): void
    {
        $pick = new QmMapPick([
            'side_index' => '5'
        ]);

        $this->assertIsInt($pick->side_index);
        $this->assertEquals(5, $pick->side_index);
    }

    /**
     * Test relationships are defined.
     */
    public function test_user_relationship_exists(): void
    {
        $pick = new QmMapPick();
        $relation = $pick->user();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    public function test_ladder_relationship_exists(): void
    {
        $pick = new QmMapPick();
        $relation = $pick->ladder();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    public function test_qm_map_relationship_exists(): void
    {
        $pick = new QmMapPick();
        $relation = $pick->qmMap();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }
}
