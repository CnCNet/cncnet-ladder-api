<?php

namespace Tests\Unit\Models;

use App\Models\QmQueueEntry;
use Carbon\Carbon;
use Tests\TestCase;

class QmQueueEntryTest extends TestCase
{
    /**
     * Test that secondsinQueue() returns a positive value when updated_at is after created_at.
     * This verifies the bug fix where the parameter order was reversed.
     */
    public function test_seconds_in_queue_returns_positive_when_updated_after_created(): void
    {
        // Create a QmQueueEntry instance without saving to database
        $entry = new QmQueueEntry();

        // Set created_at to an earlier time
        $entry->created_at = Carbon::parse('2025-12-19 23:57:08');

        // Set updated_at to a later time (simulating touch() being called)
        $entry->updated_at = Carbon::parse('2025-12-19 23:57:11');

        // Calculate expected difference (3 seconds)
        $expectedSeconds = 3;

        // Assert that secondsinQueue() returns the correct positive value
        $this->assertEquals($expectedSeconds, $entry->secondsinQueue());

        // Assert that the result is positive
        $this->assertGreaterThan(0, $entry->secondsinQueue());
    }

    /**
     * Test that secondsinQueue() calculates the correct time difference.
     */
    public function test_seconds_in_queue_calculates_correct_time_difference(): void
    {
        $entry = new QmQueueEntry();

        // Entry created at 00:12:20
        $entry->created_at = Carbon::parse('2025-12-20 00:12:20');

        // Updated 397 seconds later at 00:18:57
        $entry->updated_at = Carbon::parse('2025-12-20 00:18:57');

        // Should return 397 seconds
        $this->assertEquals(397, $entry->secondsinQueue());
    }

    /**
     * Test that secondsinQueue() returns 0 when created_at equals updated_at.
     * This happens when a queue entry is first created.
     */
    public function test_seconds_in_queue_returns_zero_when_timestamps_equal(): void
    {
        $entry = new QmQueueEntry();

        $timestamp = Carbon::parse('2025-12-19 23:57:08');
        $entry->created_at = $timestamp;
        $entry->updated_at = $timestamp;

        $this->assertEquals(0, $entry->secondsinQueue());
    }

    /**
     * Test that secondsinQueue() with longer wait times.
     * This verifies the calculation works for realistic queue times.
     */
    public function test_seconds_in_queue_with_longer_wait_time(): void
    {
        $entry = new QmQueueEntry();

        // Entry created
        $entry->created_at = Carbon::parse('2025-12-19 23:00:00');

        // 5 minutes later (300 seconds)
        $entry->updated_at = Carbon::parse('2025-12-19 23:05:00');

        $this->assertEquals(300, $entry->secondsinQueue());
        $this->assertGreaterThan(0, $entry->secondsinQueue());
    }

    /**
     * Regression test: Verify the old buggy implementation would have failed.
     * This documents what the bug was and ensures it doesn't come back.
     */
    public function test_parameter_order_matters(): void
    {
        $entry = new QmQueueEntry();

        $entry->created_at = Carbon::parse('2025-12-19 23:57:08');
        $entry->updated_at = Carbon::parse('2025-12-19 23:57:11');

        // The CORRECT implementation: created_at->diffInSeconds(updated_at)
        $correct = $entry->created_at->diffInSeconds($entry->updated_at);

        // The BUGGY implementation: updated_at->diffInSeconds(created_at)
        // This would return a negative value with the signed parameter
        $buggy = $entry->updated_at->diffInSeconds($entry->created_at, false);

        // Assert that the correct implementation is positive
        $this->assertGreaterThan(0, $correct);
        $this->assertEquals(3, $correct);

        // Assert that the buggy implementation was negative
        $this->assertLessThan(0, $buggy);
        $this->assertEquals(-3, $buggy);

        // Assert that secondsinQueue() uses the correct implementation
        $this->assertEquals($correct, $entry->secondsinQueue());
    }
}
