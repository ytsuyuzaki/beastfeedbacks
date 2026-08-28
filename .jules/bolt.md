## 2025-08-23 - WP_Query found_posts Optimization in BeastFeedbacks
**Learning:** `WP_Query` when used solely for `found_posts` in `BeastFeedbacks::get_like_count()` defaults to retrieving full `WP_Post` objects, fetching default page limits (10), and priming term/meta caches for all returned items.
**Action:** Always set `posts_per_page => 1`, `fields => 'ids'`, `no_found_rows => false`, `update_post_term_cache => false`, and `update_post_meta_cache => false` when counting posts with `WP_Query` to minimize database overhead and PHP memory usage.

## 2026-08-28 - Centralized Parent Permalink Caching in WP Admin
**Learning:** In `BeastFeedbacks_Admin`, both `add_source_filter()` and `render_source_column()` (as well as CSV export functions) independently invoke `get_permalink()` and `wp_parse_url()` for parent post IDs during the same request lifecycle.
**Action:** Centralize parent post permalink resolution in a shared helper method `get_parent_permalink_data()` using static memory caching so that permalinks and parsed URL paths computed during filter dropdown rendering are reused instantly when rendering table rows and exporting CSVs.
