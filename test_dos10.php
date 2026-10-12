<?php
// Look again at the description:
// "Potential Denial of Service via Resource Exhaustion in sanitize_param_value"
// "Rationale: Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing."
// Wait, if an attacker sends an array like `['a' => 'b', 'c' => 'd']`, it parses it.
// What if an attacker sends an array with 50 keys, and each value is an array, and each of those... wait, it doesn't process them because depth is capped at 2.
// BUT what if the vulnerability is related to the string keys?
// Look at this:
// 			$sanitized_array = array();
// 			$max_items       = 50;
// 			$item_count      = 0;
//
// 			foreach ( $value as $key => $item ) {
// 				if ( $item_count >= $max_items ) {
// 					break;
// 				}
//
// 				$sanitized_item_key = sanitize_text_field( wp_unslash( (string) $key ) );

// `wp_unslash` unslashes the string. If the string is a massive string, `wp_unslash` takes memory.
// Wait, `$key` is an array key. PHP array keys are limited in size?
// NO! PHP array keys are NOT strictly limited to small strings. They can be very large strings!
// If an attacker sends `POST /?foo[<100MB string>]=bar`
// PHP will parse the 100MB string as the array key.
// Then `sanitize_param_value` gets an array with a 100MB key.
// `(string) $key` is 100MB.
// `wp_unslash` copies it.
// `sanitize_text_field` copies it and runs regex on it.
// This is exactly the resource exhaustion!

// How to mitigate?
// We should check the length of `$key` BEFORE calling `wp_unslash` and `sanitize_text_field`.
// Currently:
// 				$sanitized_item_key = sanitize_text_field( wp_unslash( (string) $key ) );
// 				$sanitized_item_key = mb_substr( $sanitized_item_key, 0, 100 );
// If we change it to:
// 				$sanitized_item_key = (string) $key;
// 				$sanitized_item_key = mb_substr( $sanitized_item_key, 0, 100 );
// 				$sanitized_item_key = sanitize_text_field( wp_unslash( $sanitized_item_key ) );
// That fixes the key length issue.

// BUT wait... the rationale says: "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing."
// This rationale doesn't match the key length hypothesis perfectly. It specifically mentions "complex array structures" and "deep parsing".
// Could the issue be that `extract_post_params` is expected to return a simple array, but it allows nested arrays?
// If we look at the PHPUnit test `extract_post_params_sanitizes_keys_and_handles_nested_arrays`:
// 			'nested_array'                  => array( 'level1' => array( 'level2' ) ),
// And it expects `array( 'level1' => '' )`
// So it explicitly tests that nested arrays are handled up to a depth.

// What if "deep parsing" means we should reject arrays inside `sanitize_param_value` entirely?
// But `sanitize_param_value` has a `$max_depth` parameter. Why would it have a depth parameter if it's supposed to reject arrays entirely?
// Wait, is there a bug in the depth check?
// 	private function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
// 		if ( $current_depth > $max_depth ) {
// 			return '';
// 		}
//
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {
// 				return '';
// 			}
//
// If `current_depth` is 2, and `max_depth` is 2, it returns ''.
// This means it only processes arrays at depth 1.
// At depth 1, `current_depth` is 1, `max_depth` is 2. `1 === 2` is false.
// It loops through items.
// For each item, it calls `sanitize_param_value( $item, 2, 2 )`.
// If `$item` is an array, `current_depth` is 2, `max_depth` is 2. `2 === 2` is true. It returns ''.
// If `$item` is a string, `current_depth` is 2, `max_depth` is 2. It skips `is_array` block, and processes the string!
// Is processing the string at depth 2 "deep parsing"?
// Yes! If a user submits `nested_array => ['level1' => '<script>huge payload</script>']`
// It will parse it.
