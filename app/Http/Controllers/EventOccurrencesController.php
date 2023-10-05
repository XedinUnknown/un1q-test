<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ListEventOccurrencesRequest;
use App\Models\EventOccurrence;
use DateTimeImmutable;

/**
 * API operations on {@link EventOccurrence}.
 */
class EventOccurrencesController extends Controller
{
    protected const DATETIME_FORMAT_MYSQL = 'Y-m-d H:i:s';
    protected const PAGE_DEFAULT = '1';
    protected const PAGE_LIMIT_DEFAULT = '10';

    /**
     * Lists event occurrences between `from` and `to` datetimes,
     * optionally belonging to an event with the specified `event_id`.
     *
     * - `from`* - A datetime string representing the start of the event occurrence range.
     * - `to`* - A datetime string representing the end of the event occurrence range.
     * - `event_id` - The ID of an event to get occurrences for; default: all events.
     */
    public function index(ListEventOccurrencesRequest $request)
    {
        $queryParams = $request->validated();
        $fromDate = new DateTimeImmutable($queryParams['from']);
        $toDate = new DateTimeImmutable($queryParams['to']);
        $eventId = isset($queryParams['event_id']) ? intval($queryParams['event_id']) : null;
        $limit = isset($queryParams['limit']) ? intval($queryParams['limit']) : static::PAGE_LIMIT_DEFAULT;

        $query = EventOccurrence::where('start', '>=', $fromDate)
            ->where('end', '<=', $toDate);
        if ($eventId !== null) {
            $query->where('event_id', $eventId);
        } else {
            // Exclude orphaned
            $query->whereNotNull('event_id');
        }

        $occurrences = $query->paginate($limit);
        $occurrencesData = array_map(fn(EventOccurrence $occurrence): array => [
            'event_id' => $occurrence->event_id,
            'start' => $occurrence->start->format(static::DATETIME_FORMAT_MYSQL),
            'end' => $occurrence->end->format(static::DATETIME_FORMAT_MYSQL),
        ], $occurrences->all());

        return response([
            'items' => $occurrencesData,
            'page' => $occurrences->currentPage(),
            'limit' => $occurrences->perPage(),
            'total' => $occurrences->total(),
        ]);
    }
}
