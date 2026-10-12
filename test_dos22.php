<?php
// Look again at the string key resource exhaustion.
// If an attacker sends `['a' => 'b']`, but 'a' is a 100MB string.
// `sanitize_text_field( wp_unslash( (string) $key ) )` is called on a 100MB string.
// `wp_unslash` -> huge memory allocation.
// `sanitize_text_field` -> huge memory allocation and regex parsing.
// This is definitely a DOS.
// And we also have `is_array( $value )` traversal where we might traverse complex arrays.
// The rationale specifically says: "rejecting complex array structures entirely or flattening them before deep parsing"
// So maybe we can change the array logic to reject complex arrays entirely.

// What if "rejecting complex array structures entirely" means simply:
// If it's an array, we json_encode it.
// Let's look at `extract-post-params-test.php`
// 		$this->assertSame( array( 'fast', 'helpful' ), $result['tags'] );
// If we json_encode it, it would be `["fast","helpful"]`. The test would fail.
// Okay, so we can't flatten it into JSON. We have to return an array.

// What if the fix is:
// 		if ( is_array( $value ) ) {
// 			// Reject complex array structures entirely
//          $is_complex = false;
//          foreach($value as $v) {
//              if (is_array($v) || is_object($v)) { $is_complex = true; break; }
//          }
//          if ($is_complex) {
//              return '';
//          }
//          // ...
// This explicitly matches "rejecting complex array structures entirely".

// Let's implement this and check if tests pass.
