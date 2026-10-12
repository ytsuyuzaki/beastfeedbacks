<?php
// Is it possible that `wp_unslash` is vulnerable to deep arrays?
// `wp_unslash` recursively unslashes arrays!
// Wait... let me check `includes/class-beastfeedbacks-public.php` to see if `wp_unslash` is called on arrays.
// No, it's called on `(string) $key` and `(string) $value`.
// The docblock says: "Unslashing is performed inside bounded traversal so rejected deep/excess branches are never unslashed."

// What if the vulnerability is the key itself?
// If a user sends:
// $_POST['a'] = ['very long string 100MB' => 'b']
// The key is unslashed and sanitized before being truncated.
// Let's fix this specific part and see if it makes sense.
// 				$sanitized_item_key = (string) $key;
// 				$sanitized_item_key = mb_substr( $sanitized_item_key, 0, 100 );
// 				$sanitized_item_key = sanitize_text_field( wp_unslash( $sanitized_item_key ) );
// That prevents DoS via huge keys.
// AND for the scalar values:
// 		$unslashed_val = wp_unslash( (string) $value );
// Wait, $value is unbounded before being passed to wp_unslash.
// 		$str_val       = mb_substr( (string) $value, 0, 2000 );
// 		$unslashed_val = wp_unslash( $str_val );
// That would prevent DoS on huge values!

// Let's look at the rationale again:
// "Rationale: Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// Why does it talk about complex array structures?
// Oh!
// What if we just json_encode arrays, and then sanitize them as a single string?
// `flattening them before deep parsing` -> JSON encoding is flattening.
// If we flatten them, we can enforce the length limit (2000 chars) on the whole JSON string, and then we don't need to recursively parse them!
// 	private function sanitize_param_value( $value ) {
// 		if ( is_array( $value ) ) {
// 			// Flatten complex array structures before parsing to prevent resource exhaustion
// 			$value = wp_json_encode( $value );
// 		}
// Wait, if we flatten them to JSON, we break the test that expects:
// `$this->assertSame( array( 'fast', 'helpful' ), $result['tags'] );`
// The test explicitly wants an array.

// Okay, what if "rejecting complex array structures entirely" means:
// If the array contains another array, return `''` instead of traversing it.
// 	private function sanitize_param_value( $value ) {
// 		if ( is_array( $value ) ) {
//          // reject if it's multidimensional
//          foreach($value as $item) {
//              if (is_array($item) || is_object($item)) return '';
//          }
//          // Then we don't need recursion!
//          $sanitized_array = array();
//          $max_items = 50;
//          // ...
// 		}
// This completely removes the need for `$current_depth` and `$max_depth`!
// And it eliminates any risk of deep parsing!
