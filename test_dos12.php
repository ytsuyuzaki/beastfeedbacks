<?php
// So how do we reject complex array structures entirely or flatten them BEFORE deep parsing?
// Currently:
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {
// 				return '';
// 			}
//
// 			$sanitized_array = array();
// 			$max_items       = 50;
// 			$item_count      = 0;
//
// 			foreach ( $value as $key => $item ) {
// 				...
// 				$sanitized_array[ $sanitized_item_key ] = $this->sanitize_param_value( $item, $current_depth + 1, $max_depth );
// 				...
// 			}
//
// "rejecting complex array structures entirely"
// Could it mean we reject any array that contains an array?
// If we reject an array that contains an array, we can just do:
// foreach ($value as $item) { if (is_array($item)) return ''; }
// Or something like that.

// Let's re-read the rationale:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// "deep parsing" refers to `sanitize_param_value` recursively calling itself or parsing the array structure.
// Wait, if an attacker sends an array of 50 items. Each is an array.
// `sanitize_param_value` traverses the 50 items. For each, it calls `sanitize_param_value` at depth 2.
// At depth 2, it is an array, so it returns ''.
// BUT BEFORE it returns '', it unslashes and sanitizes the KEY.
// 				$sanitized_item_key = sanitize_text_field( wp_unslash( (string) $key ) );
// And it does this for ALL 50 keys, even though the value is an array that will just be discarded (turned to '')!
// Wait! The depth 2 array is the VALUE (`$item`). The `$key` is at depth 1.
// At depth 1, it sanitizes the key, and then calls `sanitize_param_value($item, 2, 2)`.
// If `$item` is an array, it returns ''.
// So we just did work to sanitize the key, and the value becomes ''.
// What if it's deeper? Max depth is 2. So it only goes 1 level deep.

// Wait. Is the vulnerability just that PHP can crash if you give it too much recursion? No, depth is 2.
// Is it possible to bypass the depth check?
// No, `$current_depth + 1` is safe.
// Let's look at the PHP docs or known WordPress patterns.
// Often, input should just be flattened.
// E.g., if you have `tags => ['a', 'b']`, you could flatten it to `'a, b'`.
// But the test asserts it's an array!
// `$this->assertSame( array( 'fast', 'helpful' ), $result['tags'] );`

// Is there a way for `$current_depth` to wrap around? It's typed `int`.
// What if we just reject arrays completely?
// But the test expects an array.
