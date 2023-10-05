<?php

use App\Models\Event;
use App\Models\EventOccurrence;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\AssertionFailedError;
use RRule\RRule;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use DatabaseMigrations;

    protected const DATETIME_FORMAT_MYSQL = 'Y-m-d H:i:s';
    protected const DATETIME_FORMAT_ISO8601 = 'Y-m-d\TH:i:s';

    /**
     * The creation of an event via the API should lead to the creation of all occurrences for it.
     */
    public function testStoreEventCreateOccurrences(): void
    {
        // From tomorrow, 9-to-5 for a few days
        $occurencesAmt = rand(3, 9);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $start = $now->modify('9am next day');
        $end = $start->modify('+9 hours');
        $title = uniqid('title');
        $until = $start->modify(sprintf('+%1$d days', $occurencesAmt - 1));

        // Create a new event
        $this->followingRedirects(); // Creation redirects to `show` route
        $eventsResponse = $this->postJson('/api/events', [
            'start' => $start->format(self::DATETIME_FORMAT_ISO8601),
            'end' => $end->format(self::DATETIME_FORMAT_ISO8601),
            'title' => $title,
            'frequency' => 'daily',
            'interval' => 1,
            'until' => $until->format(self::DATETIME_FORMAT_ISO8601),
        ]);
        $eventsResponse->assertStatus(200);

        // Retrieve new event ID
        $eventsResponseData = json_decode($eventsResponse->content());
        $this->assertObjectHasProperty(
            'id',
            $eventsResponseData,
            'New event ID must be present in the response'
        );
        $eventId = intval($eventsResponseData->id);

        // Get new event's occurrences
        $occurrencesResponse = $this->get(sprintf('/api/occurrences?%1$s', http_build_query([
            // Arbitrary yet wide range that covers all occurrences and significantly more
            'from' => $start->modify('-1 month')->format(self::DATETIME_FORMAT_ISO8601),
            'to' => $end->modify('+1000 year')->format(self::DATETIME_FORMAT_ISO8601),
            // Only get occurrences for the new event
            'event_id' => $eventId,
            // Make sure we are able to get all occurrences
            'limit' => $occurencesAmt,
        ])));
        $occurrencesResponse->assertStatus(200);
        $occurrences = json_decode($occurrencesResponse->content())->items;

        // Inspect retrieved occurrences in case of failure
        try {
            $this->assertEquals(
                $occurencesAmt,
                count($occurrences),
                'Wrong number of occurrences created',
            );
        } catch (AssertionFailedError $e) {
            // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Found
            var_dump($occurrences);
            throw $e;
        }

        // Test the occurrences
        foreach ($occurrences as $occurrence) {
            $startTime = (new DateTimeImmutable($occurrence->start))->format('H:i:s');
            $endTime = (new DateTimeImmutable($occurrence->end))->format('H:i:s');
            $this->assertEquals($start->format('H:i:s'), $startTime, 'Wrong occurrence start time');
            $this->assertEquals($end->format('H:i:s'), $endTime, 'Wrong occurrence end time');
        }
    }

    /**
     * Tests overlapping occurrences failure and at the same time total event creation failure due to that
     */
    public function testStoreEventFailsEntirelyIfOccurrencesOverlap()
    {
        // Happens once
        $now = now();
        $start = $now->copy();
        $end = $start->copy()->add(new DateInterval('PT1M')); // 1 minute later
        $until = $start->copy()->modify('+1 day');
        $title = uniqid('title');
        $eventData = [
            'start' => $start->format(self::DATETIME_FORMAT_ISO8601),
            'end' => $end->format(self::DATETIME_FORMAT_ISO8601),
            'title' => $title,
            'frequency' => 'daily',
            'interval' => 1,
            'until' => $until->format(self::DATETIME_FORMAT_ISO8601),
        ];

        // Create existing event
        Event::create($eventData);

        // Create new identical event, which is sure to overlap
        $this->followingRedirects(); // Creation redirects to `show` route
        $eventsResponse = $this->postJson('/api/events', $eventData);

        // Conflict response
        $eventsResponse->assertStatus(409);
    }

    public function testDeleteEventAlsoRemovesOccurrences()
    {
        // From tomorrow, 9-to-5 for a few days
        $occurencesAmtA = rand(3, 9);
        $nowA = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startA = $nowA->modify('9am next day');
        $endA = $startA->modify('+9 hours');
        $titleA = uniqid('titleA');
        $frequencyA = 'daily';
        $intervalA = 1;
        $untilA = $startA->modify(sprintf('+%1$d days', $occurencesAmtA - 1));
        $eventA = Event::create([
            'start' => $startA->format(self::DATETIME_FORMAT_ISO8601),
            'end' => $endA->format(self::DATETIME_FORMAT_ISO8601),
            'title' => $titleA,
            'frequency' => $frequencyA,
            'interval' => $intervalA,
            'until' => $untilA->format(self::DATETIME_FORMAT_ISO8601),
        ]);

        // From tomorrow, 19:00 to 21:00 for a few days
        $occurencesAmtB = rand(3, 9);
        $nowB = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startB = $nowB->modify('19:00 next day');
        $endB = $startB->modify('+2 hours');
        $titleB = uniqid('titleB');
        $frequencyB = 'daily';
        $intervalB = 1;
        $untilB = $startB->modify(sprintf('+%1$d days', $occurencesAmtB - 1));
        $eventB = Event::create([
            'start' => $startB->format(self::DATETIME_FORMAT_ISO8601),
            'end' => $endB->format(self::DATETIME_FORMAT_ISO8601),
            'title' => $titleB,
            'frequency' => $frequencyB,
            'interval' => $intervalB,
            'until' => $untilB->format(self::DATETIME_FORMAT_ISO8601),
        ]);

        // Remove one via API
        $this->delete(sprintf('/api/events/%1$d', $eventA->id));
        $this->assertCount(1, Event::all(), 'More events than expected');
        $this->assertNull(Event::find($eventA->id), 'Event not deleted');

        // Test the other
        $remainingEvent = Event::find($eventB->id);
        $this->assertInstanceOf(Event::class, $remainingEvent, 'Remaining event not found');
        $this->assertEquals($eventB->id, $remainingEvent->id, 'Remaining event wrong');

        // Ensure occurrences cleared
        $occurrences = EventOccurrence::all();
        $this->assertCount($occurencesAmtB, $occurrences);

        // Ensure remaining occurrences correct
        foreach ($occurrences as $occurrence) {
            $this->assertEquals($remainingEvent->id, $occurrence->event_id);
            $this->assertEquals($remainingEvent->start->format('H:i:s'), $occurrence->start->format('H:i:s'));
        }
    }

    public function testUpdatingEventErrorIfOverlapping()
    {
        // From tomorrow, 12:00 - 13:00 for a few days
        $occurencesAmtA = rand(3, 9);
        $nowA = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startA = $nowA->modify('12:00 next day');
        $endA = $startA->modify('+1 hour');
        $titleA = uniqid('titleA');
        $frequencyA = 'daily';
        $intervalA = 1;
        $untilA = $startA->modify(sprintf('+%1$d days', $occurencesAmtA - 1));
        $eventA = Event::create([
            'start' => $startA->format(self::DATETIME_FORMAT_ISO8601),
            'end' => $endA->format(self::DATETIME_FORMAT_ISO8601),
            'title' => $titleA,
            'frequency' => $frequencyA,
            'interval' => $intervalA,
            'until' => $untilA->format(self::DATETIME_FORMAT_ISO8601),
        ]);

        // From tomorrow, 14:00 - 15:00 for a few days
        $occurencesAmtB = rand(3, 9);
        $nowB = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startB = $nowB->modify('14:00 next day');
        $endB = $startB->modify('+1 hour');
        $titleB = uniqid('titleB');
        $frequencyB = 'daily';
        $intervalB = 1;
        $untilB = $startB->modify(sprintf('+%1$d days', $occurencesAmtB - 1));
        $eventB = Event::create([
            'start' => $startB->format(self::DATETIME_FORMAT_ISO8601),
            'end' => $endB->format(self::DATETIME_FORMAT_ISO8601),
            'title' => $titleB,
            'frequency' => $frequencyB,
            'interval' => $intervalB,
            'until' => $untilB->format(self::DATETIME_FORMAT_ISO8601),
        ]);

        // Update first event via API to overlap with the second
        $this->followingRedirects(); // Creation redirects to `show` route
        $response = $this->patchJson(sprintf('/api/events/%1$s', $eventA->id), [
            // Starts a minute before
            'start' => $eventB->start->modify('-1 minute')->format(self::DATETIME_FORMAT_ISO8601),
            // Ends a second earlier
            'end' => $eventB->end->modify('-1 second')->format(self::DATETIME_FORMAT_ISO8601),
        ]);
        $response->assertStatus(409);
    }

    public function testUpdatingEventCleansAndRegeneratesOccurrences()
    {
        // From tomorrow, 12:00 - 13:00 for a few days
        $occurencesAmt = rand(3, 3);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $start = $now->modify('12:00 next day');
        $end = $start->modify('+1 hour');
        $title = uniqid('title');
        $frequency = 'daily';
        $interval = 1;
        $until = $start->modify(sprintf('+%1$d days', $occurencesAmt - 1));
        $event = Event::create([
            'start' => $start->format(self::DATETIME_FORMAT_ISO8601),
            'end' => $end->format(self::DATETIME_FORMAT_ISO8601),
            'title' => $title,
            'frequency' => $frequency,
            'interval' => $interval,
            'until' => $until->format(self::DATETIME_FORMAT_ISO8601),
        ]);

        // Update event via API with different start and end, overlapping with previous
        $newStart = $event->start->modify('+30 minutes');
        $newEnd = $event->start->modify('+1 hour');
        $this->followingRedirects(); // Creation redirects to `show` route
        $response = $this->patchJson(sprintf('/api/events/%1$s', $event->id), [
            'start' => $newStart->format(self::DATETIME_FORMAT_ISO8601),
            'end' => $newEnd->format(self::DATETIME_FORMAT_ISO8601),
        ]);

        // If the previous occurrences are not removed, new ones would overlap, and it would cause HTTP 409 Conflict
        $response->assertStatus(200);
    }
}
