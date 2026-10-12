<?php
// Let's read the code closely again.
// private function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
// 		if ( $current_depth > $max_depth ) {
// 			return '';
// 		}
//
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {
// 				return '';
// 			}
//          ...

// Wait. If $value is an array, and $current_depth === $max_depth, it returns ''.
// But what if $value is NOT an array, and $current_depth === $max_depth?
// It skips the `if (is_array($value))` block, and sanitizes the value!
// This means at depth = 2, it sanitizes scalars.
// What happens if we pass `sanitize_param_value($array_with_many_nested_items)`?
// If we reject complex array structures entirely, maybe we shouldn't allow arrays AT ALL unless they are simple?
// "flattening them before deep parsing"
// If we flatten an array of arrays into a single array, we avoid deep parsing completely.
// But wait, the vulnerability is in how the array is processed.
// What if there is a way to bypass the $max_items limit? No, $max_items is checked.
// What if there is an issue with `array_keys` or memory exhaustion BEFORE `sanitize_param_value`?
// No, the vulnerability is inside `sanitize_param_value`.
// The problem is that a user can supply a complex array structure, and even if we limit recursion depth, PHP has already parsed the huge array into memory during the request. But we can't control that here.
// Wait! "Potential Denial of Service via Resource Exhaustion in sanitize_param_value"
// Oh! Does the current code allow `$value` to be an array of arrays of arrays... and PHP already parsed it?
// Yes.
// What if the array is `['a' => ['b' => ['c' => ... ]]]`?
// `sanitize_param_value` takes it.
// `current_depth = 1`. `is_array` is true. `current_depth === max_depth` (1 === 2) is false.
// It iterates. `$key = 'a'`, `$item = ['b' => ...]`.
// Calls `sanitize_param_value(['b' => ...], 2, 2)`.
// `current_depth = 2`. `is_array` is true. `current_depth === max_depth` (2 === 2) is TRUE.
// It returns `''`.
// So it DOES NOT traverse deeper!
// It doesn't process deeper elements at all.

// Then where is the DoS?
