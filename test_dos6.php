<?php
// So the expected behavior for `nested_array` => array( 'level1' => array( 'level2' ) )
// is to return array( 'level1' => '' ).
// This is because max_depth is 2.
// Wait, if it rejects complex array structures entirely...
// The issue is: "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// "Potential Denial of Service via Resource Exhaustion in sanitize_param_value"
// "Vulnerable code:
//	private function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
//		if ( $current_depth > $max_depth ) {
//			return '';
//		}
//
//		if ( is_array( $value ) ) {
//			if ( $current_depth === $max_depth ) {
// "

// If we completely flatten or reject arrays...
// Currently it accepts an array. What if the array has strings, e.g. 'tags' => array('fast', 'helpful')
// In test: `$this->assertSame( array( 'fast', 'helpful' ), $result['tags'] );`
// So it needs to support arrays of strings.

// How about flattening?
// Flattening before deep parsing?
// But wait, what is the ACTUAL vulnerability?
// If we send $_POST['key'] = array('foo' => 'bar'), $value is an array.
// `sanitize_param_value` traverses it.
// We call wp_unslash on the key: `wp_unslash( (string) $key )`
// And we call `mb_substr` and `sanitize_text_field`.
// Then we recursively call `sanitize_param_value` on the value.
// Is there a way to exhaust resources here?
// What if the array is heavily nested but within the depth limit?
// No, depth limit is 2. So it only goes 1 level deep (depth 1 -> depth 2 returns '').
// Wait! If max_depth is 2, and current_depth is 1.
// We are at depth 1. is_array() is true. We loop up to 50 items.
// For each item, we call `sanitize_param_value( $item, 2, 2 )`.
// In that recursive call, `current_depth` is 2.
// `current_depth > max_depth` (2 > 2) is false.
// `is_array($value)`: if $item is an array, `current_depth === max_depth` (2 === 2) is true -> returns ''.
// So it doesn't process arrays at depth 2!
// What if it is NOT an array at depth 2?
// It skips `if (is_array($value))` and goes to `if (is_object($value))` -> returns ''.
// Then it processes the string!
// So it DOES process strings at depth 2!
// `wp_unslash`, `preg_replace`, `wp_kses`, `sanitize_text_field`.
// That means we process up to 50 parameters, and each can have up to 50 items, which is 2500 strings!
// `wp_kses` on 2500 strings of 2000 characters each?
// Actually, `wp_kses` is expensive.
// Is there a bug with wp_unslash and regex in how they handle large inputs?
// "Unslashing is performed inside bounded traversal so rejected deep/excess branches are never unslashed."

// But wait, the issue explicitly says:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing."
// And points to:
//		if ( is_array( $value ) ) {
//			if ( $current_depth === $max_depth ) {
