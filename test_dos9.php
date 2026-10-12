<?php
// Let's reconsider the wording:
// "Rationale: Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing."
// What does "complex array structures" mean?
// It might refer to deep nesting or arrays with non-scalar values.
// In the current implementation, it tries to safely parse arrays up to depth 2.
// But if it receives an array at depth 1, it parses its keys, and for its values, it calls `sanitize_param_value` again.
// Is it possible that `wp_unslash` or `sanitize_text_field` is called on a huge key?
// Yes, as I realized, `$key` is sanitized BEFORE truncation.
// Let's look at how `extract_post_params` handles top-level keys:
// 			if ( isset( $post_data[ $post_key ] ) ) {
// 				$sanitized_key = sanitize_text_field( wp_unslash( (string) $post_key ) );
// 				// Security: Truncate oversized keys to prevent DoS/storage bloat.
// 				$sanitized_key = mb_substr( $sanitized_key, 0, 100 );
// Again, it unslashes and sanitizes BEFORE truncation.
// If the vulnerability is the key length DoS, why does the excerpt point to:
// 	private function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
// 		if ( $current_depth > $max_depth ) {
// 			return '';
// 		}
//
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {
//
// This points exactly to the depth and array checking logic.
// Is there a stack overflow possible? No, it limits depth to 2.
// Wait. What if `$value` is a huge array of arrays?
// If `$value` has 50 items. Each item is an array with 50 items. Each item is an array with 50 items...
// But it stops at depth 2.
// So depth 1 has 50 items. Each item is an array.
// At depth 2 (current_depth = 2), it is an array.
// `if ( $current_depth === $max_depth ) { return ''; }`
// It returns `''`. It does NOT iterate over the depth 2 arrays!
// Wait! Is the depth logic correct?
// `sanitize_param_value` default is `$current_depth = 1`, `$max_depth = 2`.
// `extract_post_params` calls `$this->sanitize_param_value( $post_value );`
// So it's called with `1` and `2`.
// If `$post_value` is an array:
// `is_array( $value )` is true.
// `$current_depth === $max_depth` (1 === 2) is false.
// It iterates up to 50 items.
// For each item, it calls `$this->sanitize_param_value( $item, 2, 2 );`
// If `$item` is an array:
// `is_array( $value )` is true.
// `$current_depth === $max_depth` (2 === 2) is TRUE!
// It returns `''`.
// So it only processes 1 level of nested arrays.

// WAIT! What if an attacker passes a HUGE array structure that is NOT deeply nested, but very wide?
// We limit `$max_items` to 50.
// So we only process 50 items. The rest are ignored.
// `foreach ( $value as $key => $item ) {`
// Does `foreach` on a huge array copy it or consume memory?
// PHP already consumed the memory when parsing $_POST.

// If we look at the Rationale:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// Could this mean we just shouldn't allow `is_array( $value )` at all? Or we should reject it if it's an array?
// If we reject it entirely, we break `tags => array('fast', 'helpful')`.
// BUT, maybe the "complex array structure" means rejecting arrays inside arrays?
// If we reject arrays inside arrays, isn't that what `$current_depth === $max_depth` does? It returns `''`.
// Wait... if it returns `''`, it doesn't "reject" it, it silently replaces it with `''`.

// Let's search for this specific vulnerability wording online. Maybe it's a known WordPress vulnerability or a specific SAST tool finding (like CodeQL).
