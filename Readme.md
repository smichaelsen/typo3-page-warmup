# Page Warmup

## Cache warmer for your TYPO3 pages ☕️

When content is edited in TYPO3, caches for certain pages are flushed automatically. Depending on your setup that can be dozens of pages with news plugins that are flushed when a news record is edited, for example.
This extension detects URLs of pages that have fallen out of the cache and provides a scheduler task to warm them up automatically, before your visitors have to do it.

## Usage

After installing the extension, set up a new scheduler task with the class "Page Cache Warmup Queue Worker (page_warmup)". The recommended setup is:

* Type: Recurring
* Frequency: 120
* Don't Allow Parallel Execution
* Time limit in seconds: 60

That's it. Whenever the caching framework flushes page caches based on cache tags, the affected pages will automatically get warmed up again.
