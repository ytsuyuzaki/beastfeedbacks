<?php
// Look at the Rationale again:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing"
// If we reject complex array structures ENTIRELY, we would just do:
// if ( is_array( $value ) ) {
//     return '';
// }
// But we have tests expecting tags to be an array:
// $this->assertSame( array( 'fast', 'helpful' ), $result['tags'] );
// So we can't reject ALL arrays. We can only reject nested arrays?
// If we reject nested arrays, we just do:
// if ( is_array( $value ) ) {
//     if ( $current_depth > 1 ) {
//         return '';
//     }
//     // ...
// Which is effectively what `if ( $current_depth === $max_depth ) { return ''; }` does since $current_depth starts at 1 and $max_depth is 2!

// Let's re-read the vulnerable code from the prompt:
// Vulnerable Code:
// 		if ( is_array( $value ) ) {
// 			if ( $current_depth === $max_depth ) {

// Wait! If `$max_depth` is 2.
// When `$current_depth` is 2, it returns ''.
// BUT WHAT IF AN ATTACKER SENDS AN ARRAY THAT LOOKS LIKE A STRING BUT PHP DOES SOMETHING WEIRD? No.

// Look at `json_encode` or `flattening them before deep parsing`.
// If we flatten the array before deep parsing?
// What if the array has 50 keys, and we flatten it into a string?
// `implode(',', $value)`?
// If we flatten it, we can just sanitize a single string instead of 50 strings.
// But the tests expect:
// `$this->assertSame( array( 'fast', 'helpful' ), $result['tags'] );`
// So we must return an array for `tags`.

// Is there a bug where `$current_depth` can be manipulated? No, it's passed as an argument.
// What about `$value`? Is there a possibility that `is_array` is bypassed but it's iterable? No.
// Let's think about "Resource Exhaustion in sanitize_param_value".
// 1. Recursive array parsing can cause stack overflow if not bounded. But it is bounded by $max_depth.
// 2. Traversing a large array can exhaust CPU. But it is bounded by $max_items.
// 3. String manipulation on huge strings can exhaust memory/CPU.
//    - keys: unslashed and sanitized before truncation (as I noticed).
//    - values: unslashed, regex replaced, kses'd, sanitized before truncation!
// Look at how values are handled:
// 		$unslashed_val = wp_unslash( (string) $value );
// 		$no_script_val = preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', '', $unslashed_val );
// 		$no_tags_val   = wp_kses( $no_script_val, array() );
// 		$sanitized_val = wp_specialchars_decode( sanitize_text_field( $no_tags_val ), ENT_QUOTES );
// 		return mb_substr( $sanitized_val, 0, 2000 );
// The truncation is at the END.
// If an attacker sends a 100MB string in `$_POST['comment']`, it will go through `wp_unslash`, `preg_replace`, `wp_kses`, and `sanitize_text_field` BEFORE being truncated to 2000 characters!
// `wp_kses` on a 100MB string will easily cause a DoS (memory exhaustion and CPU exhaustion).
// This is definitely a vulnerability.
// BUT the rationale says:
// "Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing."
// This rationale DOES NOT match the string value hypothesis. It explicitly mentions "complex array structures" and "deep parsing".
