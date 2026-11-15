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

    /**
     * Test that isVeto() returns true when side_index is -1.
     */
    public function test_is_veto_returns_true_for_minus_one(): void
    {
        $pick = new QmMapPick();
        $pick->side_index = -1;

        $this->assertTrue($pick->isVeto());
    }

    /**
     * Test that isVeto() returns false when side_index is not -1.
     */
    public function test_is_veto_returns_false_for_other_values(): void
    {
        $pick = new QmMapPick();

        $pick->side_index = -3;
        $this->assertFalse($pick->isVeto());

        $pick->side_index = 0;
        $this->assertFalse($pick->isVeto());

        $pick->side_index = 5;
        $this->assertFalse($pick->isVeto());
    }

    /**
     * Test that isRandom() returns true when side_index is -3.
     */
    public function test_is_random_returns_true_for_minus_three(): void
    {
        $pick = new QmMapPick();
        $pick->side_index = -3;

        $this->assertTrue($pick->isRandom());
    }

    /**
     * Test that isRandom() returns false when side_index is not -3.
     */
    public function test_is_random_returns_false_for_other_values(): void
    {
        $pick = new QmMapPick();

        $pick->side_index = -1;
        $this->assertFalse($pick->isRandom());

        $pick->side_index = 0;
        $this->assertFalse($pick->isRandom());

        $pick->side_index = 5;
        $this->assertFalse($pick->isRandom());
    }

    /**
     * Test that validateSideIndex() returns true for -3 (random).
     */
    public function test_validate_side_index_accepts_random(): void
    {
        $ladder = new Ladder();
        $ladder->id = 1;

        $this->assertTrue(QmMapPick::validateSideIndex(-3, $ladder));
    }

    /**
     * Test that validateSideIndex() returns true for -1 (veto).
     */
    public function test_validate_side_index_accepts_veto(): void
    {
        $ladder = new Ladder();
        $ladder->id = 1;

        $this->assertTrue(QmMapPick::validateSideIndex(-1, $ladder));
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
        $this->assertFalse(QmMapPick::validateSideIndex(-2, $ladder));
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
