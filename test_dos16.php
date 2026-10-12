<?php
// Look at the specific lines of the Vulnerable Code:
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {
// 				return '';
// 			}
//
// What if we change it to:
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth >= $max_depth ) {
// 				return '';
// 			}
// Wait, $current_depth is 1, max_depth is 2. So it will process the array.
// What if "rejecting complex array structures entirely" means we just reject ANY array?
// If we reject any array, `tags` breaks.
// What if we reject any array that contains an array?
// Let's check `is_array( $value )`:
// We can check if any of the items is an array. If so, return ''.
// foreach ($value as $item) { if (is_array($item)) return ''; }
// But wait, the test expects `nested_array` to return `['level1' => '']`!
// If we return '' for the whole thing, the test will fail!

// Wait, DOES the test expect `nested_array` to return `['level1' => '']`?
// Let's check the test file again.
// 			'nested_array'                  => array( 'level1' => array( 'level2' ) ),
// 		$this->assertSame( array( 'level1' => '' ), $result['nested_array'] );
// Yes, it expects `['level1' => '']`.

// What if the test is flawed and SHOULD be updated?
// "Ensure the fix doesn't introduce new vulnerabilities"
// "Preserve existing functionality"
// Preserving existing functionality might mean `tags` works, but `nested_array` is an edge case that was just documented in the test, not a desired feature!
// The plugin is "BeastFeedbacks". What does it submit?
// It submits feedback forms.
// Feedback forms typically have scalar fields (name, email, comment) and maybe array fields for checkboxes (e.g. `tags[] = fast`, `tags[] = helpful`).
// They DO NOT have nested arrays like `metadata[user][address][street]`.
// So rejecting complex array structures (arrays containing arrays) entirely makes perfect sense!
// If an array contains an array, we can just return `''` for the WHOLE array.
// OR we can flatten it. But rejecting is safer.
