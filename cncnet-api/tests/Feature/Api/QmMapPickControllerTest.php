<?php

namespace Tests\Feature\Api;

use App\Models\MapTier;
use App\Models\QmMapPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\Auth\JwtAuthHelper;
use Tests\Feature\Api\Qm\MatchRequest\QmPlayerHelper;
use Tests\TestCase;

class QmMapPickControllerTest extends TestCase
{
    use RefreshDatabase;
    use QmPlayerHelper;
    use JwtAuthHelper;

    private $user;
    private $ladder;
    private $qmMaps;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser('testuser');
        $this->ladder = $this->makeLadder(2);
        $this->qmMaps = $this->ladder->qmMaps;
    }

    /**
     * Test bulk update creates new map picks successfully.
     */
    public function test_bulk_update_creates_new_picks(): void
    {
        $payload = [
            'ladder_id' => $this->ladder->id,
            'user_id' => $this->user->id,
            'mapChoices' => [
                [
                    'qm_map_id' => $this->qmMaps[0]->id,
                    'side_index' => 4,
                ],
                [
                    'qm_map_id' => $this->qmMaps[1]->id,
                    'side_index' => -1, // veto
                ],
            ]
        ];

        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated' => 2,
        ]);

        // Verify database records
        $this->assertDatabaseHas('qm_map_picks', [
            'user_id' => $this->user->id,
            'ladder_id' => $this->ladder->id,
            'qm_map_id' => $this->qmMaps[0]->id,
            'side_index' => 4,
        ]);

        $this->assertDatabaseHas('qm_map_picks', [
            'user_id' => $this->user->id,
            'ladder_id' => $this->ladder->id,
            'qm_map_id' => $this->qmMaps[1]->id,
            'side_index' => -1,
        ]);
    }

    /**
     * Test bulk update updates existing picks.
     */
    public function test_bulk_update_updates_existing_picks(): void
    {
        // Create initial pick
        QmMapPick::create([
            'user_id' => $this->user->id,
            'ladder_id' => $this->ladder->id,
            'qm_map_id' => $this->qmMaps[0]->id,
            'side_index' => 2,
        ]);

        // Update to different faction
        $payload = [
            'ladder_id' => $this->ladder->id,
            'user_id' => $this->user->id,
            'mapChoices' => [
                [
                    'qm_map_id' => $this->qmMaps[0]->id,
                    'side_index' => 5,
                ],
            ]
        ];

        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $payload);

        $response->assertStatus(200);

        // Verify updated
        $this->assertDatabaseHas('qm_map_picks', [
            'user_id' => $this->user->id,
            'qm_map_id' => $this->qmMaps[0]->id,
            'side_index' => 5,
        ]);

        // Verify only one record exists (not duplicated)
        $this->assertEquals(1, QmMapPick::where('user_id', $this->user->id)->count());
    }

    /**
     * Test user cannot update another user's picks.
     */
    public function test_bulk_update_rejects_unauthorized_user(): void
    {
        $otherUser = $this->makeUser('otheruser');

        $payload = [
            'ladder_id' => $this->ladder->id,
            'user_id' => $otherUser->id, // Trying to modify other user
            'mapChoices' => [
                [
                    'qm_map_id' => $this->qmMaps[0]->id,
                    'side_index' => 4,
                ],
            ]
        ];

        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $payload);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Unauthorized to modify other users\' preferences'
        ]);
    }

    /**
     * Test validation rejects invalid qm_map_id.
     */
    public function test_bulk_update_rejects_invalid_qm_map_id(): void
    {
        $payload = [
            'ladder_id' => $this->ladder->id,
            'user_id' => $this->user->id,
            'mapChoices' => [
                [
                    'qm_map_id' => 99999, // Invalid ID
                    'side_index' => 4,
                ],
            ]
        ];

        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $payload);

        $response->assertStatus(422); // Validation error
    }

    /**
     * Test validation rejects map from wrong ladder.
     */
    public function test_bulk_update_rejects_map_from_wrong_ladder(): void
    {
        $otherLadder = $this->makeLadder(2);
        $otherMap = $otherLadder->qmMaps->first();

        $payload = [
            'ladder_id' => $this->ladder->id,
            'user_id' => $this->user->id,
            'mapChoices' => [
                [
                    'qm_map_id' => $otherMap->id, // Map from different ladder
                    'side_index' => 4,
                ],
            ]
        ];

        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $payload);

        $response->assertStatus(400);
        $response->assertJsonFragment(['error']);
    }

    /**
     * Test validation rejects invalid side_index.
     */
    public function test_bulk_update_rejects_invalid_side_index(): void
    {
        $payload = [
            'ladder_id' => $this->ladder->id,
            'user_id' => $this->user->id,
            'mapChoices' => [
                [
                    'qm_map_id' => $this->qmMaps[0]->id,
                    'side_index' => 999, // Out of range
                ],
            ]
        ];

        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $payload);

        $response->assertStatus(400);
        $response->assertJsonFragment(['error']);
    }

    /**
     * Test GET /api/v1/user/ladders includes mapChoices.
     */
    public function test_get_user_ladders_includes_map_choices(): void
    {
        // Create some map picks
        QmMapPick::create([
            'user_id' => $this->user->id,
            'ladder_id' => $this->ladder->id,
            'qm_map_id' => $this->qmMaps[0]->id,
            'side_index' => 4,
        ]);

        QmMapPick::create([
            'user_id' => $this->user->id,
            'ladder_id' => $this->ladder->id,
            'qm_map_id' => $this->qmMaps[1]->id,
            'side_index' => -1,
        ]);

        $response = $this
            ->jwtAuth($this->user)
            ->getJson('/api/v1/user/ladders');

        $response->assertStatus(200);

        $json = $response->json();

        // Find our test ladder in response
        $testLadder = collect($json)->firstWhere('id', $this->ladder->id);

        $this->assertNotNull($testLadder);
        $this->assertArrayHasKey('mapChoices', $testLadder);
        $this->assertCount(2, $testLadder['mapChoices']);

        // Verify map choices content
        $this->assertEquals($this->qmMaps[0]->id, $testLadder['mapChoices'][0]['qm_map_id']);
        $this->assertEquals(4, $testLadder['mapChoices'][0]['side_index']);

        $this->assertEquals($this->qmMaps[1]->id, $testLadder['mapChoices'][1]['qm_map_id']);
        $this->assertEquals(-1, $testLadder['mapChoices'][1]['side_index']);
    }

    /**
     * Test GET /api/v1/user/ladders returns empty array when no picks.
     */
    public function test_get_user_ladders_returns_empty_array_when_no_picks(): void
    {
        $response = $this
            ->jwtAuth($this->user)
            ->getJson('/api/v1/user/ladders');

        $response->assertStatus(200);

        $json = $response->json();
        $testLadder = collect($json)->firstWhere('id', $this->ladder->id);

        $this->assertNotNull($testLadder);
        $this->assertArrayHasKey('mapChoices', $testLadder);
        $this->assertEmpty($testLadder['mapChoices']);
    }

    /**
     * Test rate limiting (10 requests per minute).
     */
    public function test_rate_limiting_enforced(): void
    {
        $payload = [
            'ladder_id' => $this->ladder->id,
            'user_id' => $this->user->id,
            'mapChoices' => [
                [
                    'qm_map_id' => $this->qmMaps[0]->id,
                    'side_index' => 4,
                ],
            ]
        ];

        // Make 10 requests (should all succeed)
        for ($i = 0; $i < 10; $i++) {
            $response = $this
                ->jwtAuth($this->user)
                ->postJson('/api/v1/user/map-picks', $payload);

            $response->assertStatus(200);
        }

        // 11th request should be rate limited
        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $payload);

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Set up two map tiers: maps 0-1 in tier 1 (max 1 veto), maps 2-3 in tier 2 (max 2 vetoes).
     */
    private function setUpMapTiers(): void
    {
        $poolId = $this->qmMaps[0]->map_pool_id;

        MapTier::create(['map_pool_id' => $poolId, 'tier' => 1, 'name' => 'Standard', 'max_vetoes' => 1]);
        MapTier::create(['map_pool_id' => $poolId, 'tier' => 2, 'name' => 'Non-Standard', 'max_vetoes' => 2]);

        foreach ($this->qmMaps as $i => $qmMap) {
            $qmMap->map_tier = $i < 2 ? 1 : 2;
            $qmMap->save();
        }
    }

    private function vetoPayload(array $mapIndexes): array
    {
        return [
            'ladder_id' => $this->ladder->id,
            'mapChoices' => array_map(fn($i) => [
                'qm_map_id' => $this->qmMaps[$i]->id,
                'side_index' => -2,
            ], $mapIndexes),
        ];
    }

    /**
     * Veto limits come from MapTier.max_vetoes per map tier, so vetoes spread
     * across tiers within each tier's limit are accepted.
     */
    public function test_vetoes_within_per_map_tier_limits_accepted(): void
    {
        $this->setUpMapTiers();

        // 1 veto in tier 1, 2 in tier 2 — 3 total, each tier within its limit
        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $this->vetoPayload([0, 2, 3]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'updated' => 3]);
    }

    public function test_vetoes_exceeding_map_tier_limit_rejected(): void
    {
        $this->setUpMapTiers();

        // 2 vetoes in tier 1, which allows only 1
        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $this->vetoPayload([0, 1]));

        $response->assertStatus(400);
        $this->assertStringContainsString('Standard', $response->json('error'));
        $this->assertDatabaseMissing('qm_map_picks', ['user_id' => $this->user->id]);
    }

    /**
     * Invalid maps aren't shown to the client, so vetoes on them don't count.
     */
    public function test_vetoes_on_invalid_maps_not_counted(): void
    {
        $this->setUpMapTiers();

        $this->qmMaps[1]->valid = 0;
        $this->qmMaps[1]->save();

        // 2 vetoes in tier 1 (max 1), but map 1 is invalid so only 1 counts
        $response = $this
            ->jwtAuth($this->user)
            ->postJson('/api/v1/user/map-picks', $this->vetoPayload([0, 1]));

        $response->assertStatus(200);
    }
}
