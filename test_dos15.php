<?php
// Let's reconsider the "Vulnerable Code" block again.
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
// What if we just reject arrays completely if they are not explicitly needed?
// The problem is we allow nested arrays up to depth 2.
// Wait! What if the user submits an array of arrays of arrays...
// It gets parsed by PHP (up to max_input_vars).
// Then `sanitize_param_value` iterates over it.
// If it iterates over an array of 50 items, and each item is a HUGE string?
// Then `wp_unslash` and `sanitize_text_field` run 50 times on a huge string.
// But wait, if they submit a huge string, PHP memory limit would kill the script during POST parsing anyway.

// Is there a bug in how `mb_substr` or `sanitize_text_field` handle arrays?
// No, they are cast to string: `(string) $key`.
// But wait, what if `$value` is an array of strings, and we call `sanitize_param_value($item, 2, 2)`?
// At depth 2, `$item` is a string.
// `is_array($item)` is FALSE.
// So it skips `if (is_array($value))`!
// It goes to:
// 		$unslashed_val = wp_unslash( (string) $value );
// 		$no_script_val = preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', '', $unslashed_val );
// 		$no_tags_val   = wp_kses( $no_script_val, array() );
// 		$sanitized_val = wp_specialchars_decode( sanitize_text_field( $no_tags_val ), ENT_QUOTES );
// 		return mb_substr( $sanitized_val, 0, 2000 );
// So at depth 2, it DOES sanitize the string.
// If the attacker sends an array of 50 strings, each 2000 characters long? That's fine.
// What if each string is 100MB long?
// Then it calls `wp_unslash` and `wp_kses` on 50 strings, each 100MB long!
// That's 5GB of processing. Memory limit will definitely trigger, causing DoS.
// But wait, PHP's `post_max_size` is usually 8MB. So they can't send 5GB.
// Even if `post_max_size` is 8MB, running `wp_kses` on 8MB of data 50 times (if it's split into 50 chunks of 160KB) is not too bad, but `wp_kses` is notoriously slow.

// If we look at the rationale again:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// This indicates the fix is to REJECT complex array structures, or FLATTEN them.
// If we flatten an array of arrays into a string before deep parsing...
// No, `deep parsing` IS `sanitize_param_value`.
// The fix could be:
// 1. If it's an array, only allow a flat array (an array of scalars). If any element is an array, reject the WHOLE array.
// OR
// 2. Just return '' for ANY array, UNLESS it's a known key? No, `sanitize_param_value` doesn't know the key.

// If we flatten it...
// What if we just json_encode the array if it's too complex?
