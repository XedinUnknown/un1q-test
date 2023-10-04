<?php

declare(strict_types=1);

namespace App\Observers;

use App\Exceptions\OverlappingEventOccurrenceException;
use App\Models\Event;
use App\Models\EventOccurrence;
use DateInterval;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use RRule\RRule;

/**
 * Manages occurrences when events change.
 *
 * When an event is created, we respond by creating all of its occurrences.
 * This happens atomically, i.e. either all occurrences are created, or if anything fails - none of them are.
 * In the latter case, the error is still thrown.
 */
class EventObserverForOccurrences
{
    protected const DATETIME_FORMAT_MYSQL = 'Y-m-d H:i:s';

    /**
     * Handle the Event "created" event.
     */
    public function saved(Event $event): void
    {
        $duration = $event->start->diff($event->end);
        $rule = new RRule([
            'FREQ' => $event->frequency,
            'INTERVAL' => $event->interval,
            'DTSTART' => $event->start,
            'UNTIL' => $event->until,
        ]);

        try {
            DB::beginTransaction();

            foreach ($rule as $occurrence) {
                $start = $this->getFormattedDateTime($occurrence);
                $end = $this->getFormattedDateTime($occurrence->add($duration));

                $overlappingOccurrence = EventOccurrence::whereBetween('start', [$start, $end])
                    ->orWhereBetween('end', [$start, $end])
                    ->orWhereRaw(
                        sprintf(
                            '%1$s BETWEEN `start` AND `end`',
                            DB::connection()->getPdo()->quote($start)
                        )
                    )
                    ->get()
                    ->first();

                if ($overlappingOccurrence !== null) {
                    $eventStart = $this->getFormattedDateTime($event->start);
                    $eventEnd = $this->getFormattedDateTime($event->end);
                    $errorMessage = sprintf(
                        'The specified event range %1$s conflicts with an occurrence of event #%2$d with range %3$s',
                        sprintf('[%1$s, %2$s]', $eventStart, $eventEnd),
                        $overlappingOccurrence->event_id,
                        sprintf(
                            '[%1$s, %2$s]',
                            $this->getFormattedDateTime($overlappingOccurrence->start),
                            $this->getFormattedDateTime($overlappingOccurrence->end)
                        )
                    );

                    throw new OverlappingEventOccurrenceException($errorMessage);
                }

                $eventOccurrence = EventOccurrence::make([
                    'event_id' => $event->id,
                    'start' => $start,
                    'end' => $end,
                ]);
                $eventOccurrence->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getFormattedDateTime(DateTimeInterface $dateTime): string
    {
        return $dateTime->format(static::DATETIME_FORMAT_MYSQL);
    }
}
