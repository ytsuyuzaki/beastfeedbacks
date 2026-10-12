<?php
// Look at the vulnerable code.
//	private function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
//		if ( $current_depth > $max_depth ) {
//			return '';
//		}
//
//		if ( is_array( $value ) ) {
//			if ( $current_depth === $max_depth ) {
//				return '';
//			}

// Why would checking `$current_depth === $max_depth` inside `is_array` be vulnerable?
// Because if `$value` is an array and we reached the maximum depth, we return `''`.
// But what if we do NOT return `''` if it's an array and `$current_depth === $max_depth`?
// Oh wait. If we are AT max depth (e.g. 2), we want to reject arrays so we don't go DEEPER.
// But what if `$value` is a scalar?
// It skips the `is_array` block, and gets sanitized.
// Is there a bug here?
// The problem is that a user can pass a deeply nested array, e.g., `[[1]]`
// At depth 1, we iterate. We pass `[1]` to depth 2.
// At depth 2, it is an array. We are at max depth, so we return `''`.
// The vulnerability says: "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// So instead of deep parsing, we just shouldn't traverse arrays at all unless necessary!
// Let's modify the function to reject complex array structures:

class Sanitizer {
    public function sanitize_param_value( $value ) {
        if ( is_array( $value ) ) {
            // Reject complex array structures entirely
            // We only allow arrays of scalars
            $sanitized_array = array();
            $max_items       = 50;
            $item_count      = 0;

            foreach ( $value as $key => $item ) {
                if ( $item_count >= $max_items ) {
                    break;
                }

                // If any item is an array, we could reject the whole array, or just skip it
                if ( is_array( $item ) || is_object( $item ) ) {
                    // Rejecting complex array structures entirely
                    return ''; // or just continue
                }

                // sanitize key
                // ...
            }
        }
    }
}
// But wait, the existing code SUPPORTS nested arrays, up to depth 2!
// If it supports it, why is it vulnerable?
// The vulnerability is Resource Exhaustion.
// If an attacker sends an array of 50 items. Each item is an array of 50 items.
// Total items processed: 50 * 50 = 2500.
// Is 2500 items enough to cause resource exhaustion?
// If each item is a large string?
// Wait, at depth 2, the array is REJECTED because `$current_depth === $max_depth` returns `''`.
// So it DOES NOT process the 50 items at depth 2!
// Let's trace it:
// Depth 1: `is_array` is true. `current_depth` (1) === `max_depth` (2) is false.
// It iterates 50 times.
// For each iteration, it calls `sanitize_param_value($item, 2, 2)`.
// Depth 2:
// `current_depth > max_depth` (2 > 2) is false.
// If `$item` is an array:
// `is_array` is true. `current_depth === max_depth` (2 === 2) is TRUE.
// It returns `''`.
// It does NOT iterate over the 50 items at depth 2!
// So where is the resource exhaustion?
