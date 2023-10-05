<?php

namespace App\Http\Controllers;

use App\Exceptions\OverlappingEventOccurrenceException;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventsController extends Controller
{
    protected const DATETIME_FORMAT_ISO8601 = 'Y-m-d\TH:i:s';

    /**
     * Display a listing of events.
     */
    public function index()
    {
        return response()->json(Event::all());
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        $allowedFrequencies = array_map('strtolower', array_keys(Event::ALLOWED_FREQUENCIES));

        return view('create-event-form', [
            'frequencies' => $allowedFrequencies,
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(StoreEventRequest $request)
    {
        $eventData = $request->validated();
        $event = Event::make($eventData);

        /* This creates an outer transaction to facilitate child transactions,
         * which are not actually committed to DB until this transaction is committed.
         *
         * See https://medium.com/@erlandmuchasaj/understanding-laravel-transactions-eec68012d394
         *
         * This architecture allows for encapsulated start-to-end logic in each isolated location,
         * while still giving the main location enough control.
         */
        DB::beginTransaction();

        try {
            try {
                $event->save();
                DB::commit();
            } catch (OverlappingEventOccurrenceException $e) {
                DB::rollBack();

                return response()->json([
                    'errors' => [$e->getMessage()],
                ], 409);
            }
        } catch (Exception $e) { // Any exception anywhere, except those handled above
            DB::rollBack();

            throw ValidationException::withMessages([$e->getMessage()]);
        }

        return to_route('events.show', ['event' => $event->id]);
    }

    /**
     * Display an event with the specified ID.
     */
    public function show(string $id)
    {
        $event = Event::find($id);

        return response([
            'id' => $event->id,
            'start' => $event->start->format(static::DATETIME_FORMAT_ISO8601),
            'end' => $event->end->format(static::DATETIME_FORMAT_ISO8601),
            'title' => $event->title,
            'frequency' => $event->frequency,
            'interval' => $event->interval,
            'until' => $event->until,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, string $id)
    {
        $requestData = $request->validated();
        $event = Event::findOrFail($id);

        // Only change an attribute if it is specified
        if (isset($requestData['start'])) {
            $event->start = $requestData['start'];
        }
        if (isset($requestData['end'])) {
            $event->end = $requestData['end'];
        }
        if (isset($requestData['title'])) {
            $event->title = $requestData['title'];
        }
        if (isset($requestData['frequency'])) {
            $event->frequency = $requestData['frequency'];
        }
        if (isset($requestData['interval'])) {
            $event->interval = $requestData['interval'];
        }
        if (isset($requestData['until'])) {
            $event->until = $requestData['until'];
        }
        if (isset($requestData['description'])) {
            $event->description = $requestData['description'];
        }

        // See `store()`
        DB::beginTransaction();
        try {
            try {
                $event->save();
                DB::commit();
            } catch (OverlappingEventOccurrenceException $e) {
                DB::rollBack();

                return response()->json([
                    'errors' => [$e->getMessage()],
                ], 409);
            }
        } catch (Exception $e) { // Any exception anywhere, except those handled above
            DB::rollBack();

            throw ValidationException::withMessages([$e->getMessage()]);
        }

        return to_route('events.show', ['event' => $event->id]);
    }

    /**
     * Remove an event with the specified ID from storage.
     */
    public function destroy(string $id)
    {
        $id = intval($id);
        if (!$id || !($id > 0)) {
            throw ValidationException::withMessages(['ID must be a positive integer']);
        }

        Event::destroy($id);
    }
}
