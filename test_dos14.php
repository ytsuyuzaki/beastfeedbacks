<?php
// Wait. Is the vulnerability literally that a user could pass something like $_POST = ['a' => ['b' => ['c' => ['d' => ... ]]]]
// And it's an array of 50 items. Each item is an array.
// But `extract_post_params` has `apply_filters( 'beastfeedbacks_max_post_params', 50 )`.
// And `sanitize_param_value` has `$max_items = 50`.
// So it's 50 * 50 = 2500 max elements.
// But wait... what if the attacker passes an array with 50 items, but the keys are huge?
// We already established the keys are sanitized before truncation.
// If the issue is keys being sanitized before truncation, let's fix it by truncating the key FIRST.
// But the issue description is:
// "Potential Denial of Service via Resource Exhaustion in sanitize_param_value"
// "Rationale: Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing."
// This rationale doesn't match the "key length" issue. It specifically targets "complex array structures".

// Why would complex array structures cause resource exhaustion if it's limited to 50 items and depth 2?
// What if $value is NOT an array, but a very large string that looks like an array, e.g. JSON?
// No, PHP doesn't auto-decode JSON in $_POST unless it's a specific endpoint (like REST API).
// Here `extract_post_params` is just iterating $_POST.

// Let's test the hypothesis about deep arrays and how PHP parses them.
// If an attacker sends `a[][][][][][][][][]=1`, PHP parses it.
// If `sanitize_param_value` gets it, $current_depth is 1.
// $value is `[[[[[[[[[1]]]]]]]]]`.
// It is an array.
// `foreach ($value as $key => $item)` -> 1 item.
// `sanitize_param_value($item, 2, 2)`.
// $item is `[[[[[[[[1]]]]]]]]`.
// $current_depth is 2. `is_array($item)` is true.
// `$current_depth === $max_depth` (2 === 2) is TRUE.
// It returns ''.
// So it completely ignores the deep array structure!
// It takes very little time.

// What if the array is wide? 50 items.
// What if it's 50 items, and each item is an array of 50 items?
// `sanitize_param_value` will process the 50 items at depth 1.
// For each item, it calls `sanitize_param_value($item, 2, 2)`.
// At depth 2, $item is an array, it returns ''.
// So it takes 50 iterations. That's extremely fast.

// Wait. Is there ANY way it goes deeper than 2?
// `int $current_depth = 1, int $max_depth = 2`
// In `extract_post_params`:
// `$post_params[ $sanitized_key ] = $this->sanitize_param_value( $post_value );`
// This uses the defaults (1 and 2).
// In `sanitize_param_value`:
// `$sanitized_array[ $sanitized_item_key ] = $this->sanitize_param_value( $item, $current_depth + 1, $max_depth );`
// This passes `1+1` (2) and `2`.
// At depth 2:
// `if ( $current_depth > $max_depth ) { return ''; }` -> 2 > 2 is false.
// `if ( is_array( $value ) ) { if ( $current_depth === $max_depth ) { return ''; } }` -> 2 === 2 is true. Returns ''.
// So it NEVER goes to depth 3!
// So it NEVER does "deep parsing".
// Wait... if it never does deep parsing, why does the rationale say "flattening them before deep parsing"?
// Maybe the vulnerability is that an attacker CAN cause resource exhaustion BECAUSE it doesn't reject them entirely, but it iterates over them?
// No, iterating 50 items is nothing.
