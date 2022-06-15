# Page Warmup

## Cache warmer for your TYPO3 pages ☕️

When content is edited in TYPO3, caches for certain pages are flushed automatically. Depending on your setup that can be dozens of pages with news plugins that are flushed when a news record is
edited, for example.

This extension detects URLs of pages that have fallen out of the cache and provides a scheduler task to warm them up automatically, before your visitors have to do it.

## Usage

After installing the extension, set up a new scheduler task with the class "Page Cache Warmup Queue Worker (page_warmup)". The recommended setup is:

* Type: Recurring
* Frequency: 120
* Don't Allow Parallel Execution
* Time limit in seconds: 60

That's it. Whenever the caching framework flushes page caches based on cache tags, the affected pages will automatically get warmed up again.

## Under the hood

In the TYPO3 caching framework entries are flushed by tags or all at once, and it gives you no feedback about what content / information has actually been flushed - that makes it hard to to know what
needs warming up. That's why this extension collects that information when a page is cached. It remembers the URLs and cache tags in a so called _warmup reservation_. When a cache tag is flushed, the
extension can pull up all reservations matching that tag, and write the page URLs to a warmup queue.

### Detecting when a page is cached

TYPO3 doesn't have a suitable hook or middleware to react to pages being cached, so this Extension provides a cache `VariableFrontendWithWarmupReservation` and registers it for the `pages` cache. It
takes a look at all incoming cache entries and what _looks_ like the cache entry for a page, will be written into a warmup reservation.
