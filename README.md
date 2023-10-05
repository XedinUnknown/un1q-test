## Un1q - Test
A test project for [Un1q][].

### Getting Started
This is a [Laravel & Docker][] project; please consult official documentation for comprehensive startup instructions.
In short:

1. Copy `.env.example` to `.env`, optionally tweaking configuration.
   Notably, you might want to [fix ownership](https://stackoverflow.com/a/68244277/565229).
2. With no Sail or Composer, the only option seems to be
   `docker run --rm --interactive --tty --volume $PWD:/app composer install` to get Sail first.
3. You might want to add an alias to your `hosts` file for the local domain; see `.env`.
4. Generate an app key: `vendor/bin/sail artisan key:generate`.
5. Bring the environment up, building it automatically if not yet built: `vendor/bin/sail up`.
6. Prepare the database by running migrations: `vendor/bin/sail artisan:migrate`.
7. Run tests using an in-memory database: `vendor/bin/sail test`.
8. Use the API to [create a new event](http://un1q.myhost/api/events/create), etc.

### Features
The main feature of this project is its events API, which has the URI `/api`.
The API exposes several endpoints:

- `/events` - Supports all resource actions for CRUD, including `create`, but excluding `edit`:
  it isn't trivial to create a PATCH request, while the `update` action is implemented to accept
  partial data for convenience.
- `/occurrences` - Supports only the `index` action, with pagination. Accepted params:
    * `from` - Required. Defines the start of the range (inclusive), in which selected occurrences must be contained.
    * `to` - Required. Defines the end of the range (inclusive), in which selected occurrences must be contained.
    * `event_id` - Optional. If specified, only occurrences that belong to an event with the specified
      `event_id` will be selected.
    * `limit` - Optional. Defines the max number of occurrences to select on this page.
    * `page` - Optional. Defines the number of the result page. This is supported by Laravel.

All events are always accompanied by all of their occurrences, which are defined by the [RRULE][]
encoded in the event record:

- Creating an event results in its occurrences being generated. If there was no requirement to prevent
  overlap, there would be the possibility of generating occurrences in memory, one by one,
  and to send them to the client one by one using streaming. However, because no overlap is
  permitted for any occurrence of any existing event, it is more efficient and simple to query
  for occurrences directly, and thus to store them in the database. At the same time, even if working
  with in-memory occurrences, it would be beneficial to cache them; the stored occurrences are
  automatically such a cache, with added benefits. Additionally, this provides more flexibility, as
  storing occurrences as records allows for more complex filtering.

- Deleting an event results in its occurrences being deleted. This happens in the DB layer, thanks
  to the relationship defined between an event and its occurrences, and the `ON DELETE CASCADE` feature.

- Updating an event causes its occurrences to be invalidated and removed, and then re-generated, if any part of the
  occurrence rule has changed in that event. Updating fields like `title` or `description` does not cause invalidation.

## Going Forward
- It could be more efficient to invalidate or even delete records on event update via a DB trigger.
  This is likely much more efficient, but at the same time this takes control away from the application.
- It would be great to have a test to confirm that only rule updates cause occurrence invalidation.
  However, I don't know how to efficiently mock parts of that logic in Laravel in a simple way.
- It seems that the conditions for finding overlapping occurrences could be improved, as it appears that
  it may be possible to write it down in 2 expressions, instead of 3. I need to conceptualize and understand
  the alternative approach first, though, so maybe later.


[Un1q]: http://un1q.com/
[RRULE]: https://icalendar.org/iCalendar-RFC-5545/3-8-5-3-recurrence-rule.html
[Laravel & Docker]: https://laravel.com/docs/10.x/installation#laravel-and-docker
