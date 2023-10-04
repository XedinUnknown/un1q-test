<?php

declare(strict_types=1);

namespace App\Observers;

use App\Exceptions\OverlappingEventOccurrenceException;
use App\Models\Event;
use App\Models\EventOccurrence;
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
     * Changing any of these causes occurrence invalidation
     */
    protected const RRULE_ATTRIBUTES = [
        'start',
        'end',
        'interval',
        'frequency',
        'until',
    ];

    /**
     * Handle the Event "created" event.
     */
    public function saved(Event $event): void
    {
        // Do nothing if rule attributes were not modified, except for new records
        if ($event->wasChanged() && !$event->wasChanged(static::RRULE_ATTRIBUTES)) {
            return;
        }

        $duration = $event->start->diff($event->end);
        // https://github.com/rlanvin/php-rrule/wiki/RRule
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

                $overlappingOccurrence = EventOccurrence::where(function ($query) use ($start, $end): void {
                    $query->whereBetween('start', [$start, $end])
                        ->orWhereBetween('end', [$start, $end])
                        ->orWhereRaw(
                            sprintf(
                                '%1$s BETWEEN `start` AND `end`',
                                DB::connection()->getPdo()->quote($start)
                            )
                        );
                })
                    ->whereNotNull('event_id')
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

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        // Clean up if any of the rule attributes have been modified by the update
        if ($event->wasChanged(static::RRULE_ATTRIBUTES)) {
            EventOccurrence::where('event_id', $event->id)
                ->orWhereNull('event_id')
                ->delete();
        }

        // `saved` event fires next and re-generates occurrences
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted(): void
    {
        // Occurrences deleted via DB schema; see migration
    }

    protected function getFormattedDateTime(DateTimeInterface $dateTime): string
    {
        return $dateTime->format(static::DATETIME_FORMAT_MYSQL);
    }
}
