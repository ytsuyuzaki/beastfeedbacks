<?php
// Look at the rationale VERY carefully:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// Does "deep parsing" refer to `sanitize_param_value`?
// Maybe the vulnerability is that we shouldn't allow arrays AT ALL, except we need to for `tags`?
// Let's modify the code to "reject complex array structures entirely".
// How about we only allow arrays if they are purely a list of strings?
// If we check if an array contains ONLY strings/scalars before processing?

// Or maybe the fix is exactly what the rationale says:
// Change:
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {
// 				return '';
// 			}
// To:
// Reject complex array structures entirely.
// If $value is an array, we could just say: if any item is an array, reject the whole thing.
// Because we don't expect nested arrays!
// Look at the test `extract_post_params_sanitizes_keys_and_handles_nested_arrays`:
// 			'nested_array'                  => array( 'level1' => array( 'level2' ) ),
// And the expected result is:
// 		$this->assertSame( array( 'level1' => '' ), $result['nested_array'] );
// It DOES handle nested arrays, but it turns the inner array into ''.
// If we reject complex array structures ENTIRELY, we would return '' for the whole thing? No, the test expects `array( 'level1' => '' )`.

// BUT WAIT! The test might just be testing current behavior, not what's ACTUALLY secure!
// If we change it to reject complex array structures, the test might need to be updated.
// Let's search the task details again.
// "Ensure the fix doesn't introduce new vulnerabilities"
// "Preserve existing functionality" - this implies we shouldn't break the tests if possible, OR we update tests if they are testing vulnerable behavior.
