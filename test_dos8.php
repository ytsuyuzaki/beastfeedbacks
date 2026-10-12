<?php
// Let's reconsider:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// "Potential Denial of Service via Resource Exhaustion in sanitize_param_value"
// "Vulnerable Code:
// 	private function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
// 		if ( $current_depth > $max_depth ) {
// 			return '';
// 		}
//
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {
//"
// If it's returning '' when $current_depth === $max_depth for arrays, what if $max_depth is large?
// No, $max_depth defaults to 2, and in `extract_post_params` it's not even passed, so it uses the default of 2!
// Wait! Is there an issue with `$current_depth === $max_depth` check?
// If $value is a string, it DOES NOT return '' at $current_depth === $max_depth. It sanitizes it.
// Is the issue that `extract_post_params` calls `sanitize_param_value` with some specific structure?
// Maybe the vulnerability is that the traversal itself creates a huge number of sanitized strings?
// If depth is 2, it allows 1 array with 50 items. That's 50 items total.
// Wait! If the user provides a string that is a JSON encoded array, and it's parsed later? No.

// Look closely at the `foreach` loop:
// 			foreach ( $value as $key => $item ) {
// 				if ( $item_count >= $max_items ) {
// 					break;
// 				}
//
// 				$sanitized_item_key = sanitize_text_field( wp_unslash( (string) $key ) );
// 				$sanitized_item_key = mb_substr( $sanitized_item_key, 0, 100 );
// 				if ( '' === $sanitized_item_key ) {
// 					continue;
// 				}
//
// 				$sanitized_array[ $sanitized_item_key ] = $this->sanitize_param_value( $item, $current_depth + 1, $max_depth );
// 				++$item_count;
// 			}
//
// What if `$key` is very long? It gets unslashed and sanitized BEFORE truncation!
// `sanitize_text_field( wp_unslash( (string) $key ) );`
// If an attacker sends an array with a huge key: `$_POST['param'][<100MB string>] = 'val'`
// `wp_unslash` is called on a 100MB string.
// `sanitize_text_field` is called on a 100MB string.
// `mb_substr` is called AFTER.
// This is a classic resource exhaustion (DoS) vulnerability!
// If we flatten or reject complex arrays entirely? No, the hint was:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// Wait. Is the hint about rejecting arrays?
// If we reject them entirely, how does `tags => ['fast', 'helpful']` work?
// Ah! If we flatten them before deep parsing?
// Wait, if it's an array of strings like `['fast', 'helpful']`, it's just a list.
// "complex array structures" = arrays containing arrays, or associative arrays?
// If the array only has numeric keys, or if it doesn't have nested arrays...

// Let's re-read the vulnerable code excerpt specifically provided:
// 	private function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
// 		if ( $current_depth > $max_depth ) {
// 			return '';
// 		}
//
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {
//
// Why would this specific part be the "Vulnerable Code"?
// If someone passes a deeply nested array structure, PHP parses it before we even see it.
// The code traverses the array up to $max_depth.
// But wait! If $current_depth === $max_depth, we return ''.
// What if $value is an array, we return '' AT $max_depth.
// But what if $value is an array, and $current_depth < $max_depth (e.g. 1)?
// We iterate over it.
// What if the array is an associative array?
// Is there a way to trick `is_array`?
