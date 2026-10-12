<?php
// Look at the resource exhaustion from a different angle.
// What if $value is NOT an array, but a very large STRING?
// And the string contains a JSON payload?
// No, the code doesn't parse JSON here.
// Look at `extract_post_params_sanitizes_keys_and_handles_nested_arrays`:
// 			'nested_array'                  => array( 'level1' => array( 'level2' ) ),
// Expects:
// 		$this->assertSame( array( 'level1' => '' ), $result['nested_array'] );
// If `array('level2')` becomes `''`, it's because it hit the depth limit!

// The issue says:
// "Potential Denial of Service via Resource Exhaustion in sanitize_param_value"
// "Rationale: Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"

// Is it possible that the depth logic is just flawed or the resource exhaustion comes from the fact that it accepts deep nested arrays AT ALL?
// "rejecting complex array structures entirely"
// If we reject them entirely, we don't allow arrays to be returned as arrays if they have nested arrays.
// Or we just return json_encode($value) for arrays.
// Wait! If the user sends a complex array structure, PHP parses it into a large nested array.
// But PHP has `max_input_vars` to prevent this!
// Yes, but the script still does a recursive traversal.
// If an attacker bypasses `max_input_vars` by submitting JSON? No, it's `$_POST`.

// Let's implement what they suggest:
// "rejecting complex array structures entirely"
// Instead of deep parsing, if it's an array, we could check if it has nested arrays and reject it.
// Or even simpler, maybe we just json_encode it and sanitize that?
// "flattening them before deep parsing"
// Let's create a proposed fix and see if it passes tests.
