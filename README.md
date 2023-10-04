## Un1q - Test
A test project for Un1q.

### Features
The main feature of this project is its events API, which has the URI `/api`.
The API exposes several endpoints:

- `/events` - Supports all resource actions for CRUD, including `create`, but excluding `edit`:
  it isn't trivial to create a PATCH request, while the `update` action is implemented to accept
  partial data for convenience.
- `/occurrences` - Supports only the `index` action, with pagination. Accepted params:
    * `from` - Requred. Defines the start of the range, in which selected occurrences must be contained.
    * `to` - Required. Defines the end of the range, in which selected occurrences must be contained.
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


[RRULE]: https://icalendar.org/iCalendar-RFC-5545/3-8-5-3-recurrence-rule.html
