## 2025-08-23 - WP_Query found_posts Optimization in BeastFeedbacks
**Learning:** `WP_Query` when used solely for `found_posts` in `BeastFeedbacks::get_like_count()` defaults to retrieving full `WP_Post` objects, fetching default page limits (10), and priming term/meta caches for all returned items.
**Action:** Always set `posts_per_page => 1`, `fields => 'ids'`, `no_found_rows => false`, `update_post_term_cache => false`, and `update_post_meta_cache => false` when counting posts with `WP_Query` to minimize database overhead and PHP memory usage.
