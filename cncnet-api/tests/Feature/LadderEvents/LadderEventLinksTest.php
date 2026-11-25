<?php

namespace Tests\Feature\LadderEvents;

use App\Models\LadderEvent;
use App\Models\LadderHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LadderEventLinksTest extends TestCase
{
    use RefreshDatabase;
    use EventFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEvent();
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00', 'UTC'));
    }

    private function slugs(LadderHistory $history): array
    {
        return array_column(LadderEvent::linksForHistory($history->fresh('ladder')), 'slug');
    }

    // Ladder pages link to the event dashboard from the month before through the month after
    public function test_links_for_histories_around_the_event(): void
    {
        $this->assertSame([self::SLUG], $this->slugs($this->nov));
        $this->assertSame([self::SLUG], $this->slugs($this->dec));
        $this->assertSame([self::SLUG], $this->slugs($this->jan));
        $this->assertSame([self::SLUG], $this->slugs($this->makeHistory('2-2027', '2027-02-01', '2027-02-28 23:59:59')));
        $this->assertSame([], $this->slugs($this->makeHistory('10-2026', '2026-10-01', '2026-10-31 23:59:59')));
        $this->assertSame([], $this->slugs($this->makeHistory('3-2027', '2027-03-01', '2027-03-31 23:59:59')));

        $links = LadderEvent::linksForHistory($this->nov->fresh('ladder'));
        $this->assertSame('/events/' . self::SLUG, $links[0]['url']);
        $this->assertSame('upcoming', $links[0]['phase']);
    }

    public function test_no_link_without_event_row(): void
    {
        $this->event->delete();
        $this->assertSame([], $this->slugs($this->nov));
    }
}
