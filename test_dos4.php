<?php
// Look at the vulnerability description again:
// "Potential Denial of Service via Resource Exhaustion in sanitize_param_value"
// "Rationale: Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing."

// The issue might be that PHP's `is_array` checks if it's an array, but doesn't limit the *total* number of elements across all branches of the array.
// For example, if max_depth is 2, and current_depth is 1.
// At depth 1, we can have 50 items.
// Each of those 50 items could be an array of 50 items at depth 2? No, wait:
// if $current_depth === $max_depth (which is 2), it just returns ''.
// Wait, $max_depth defaults to 2!
// If $current_depth (1) < $max_depth (2):
// it iterates up to 50 times.
// In the loop, it calls `sanitize_param_value` with $current_depth + 1 (2).
// When $current_depth is 2, it reaches `if ( $current_depth === $max_depth ) { return ''; }`
// So it returns '' for each of the 50 items if they are arrays.
// BUT wait... what if they are NOT arrays?
// If they are not arrays, it bypasses the `if (is_array($value))` block.
// Wait! If they are NOT arrays, it processes them!
// Ah! If an item is a string, it does all the regex and sanitization on it!
// AND the `if ( $current_depth === $max_depth ) { return ''; }` is ONLY inside the `if ( is_array( $value ) )` block!
// Look at the code:

class VulnerabilityTest {
    public function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
        if ( $current_depth > $max_depth ) { // 2 > 2 is false. 3 > 2 is true.
            return '';
        }

        if ( is_array( $value ) ) {
            if ( $current_depth === $max_depth ) { // 2 === 2 is true, returns ''.
                return '';
            }

            // ... array processing ...
            // recursive call: $this->sanitize_param_value( $item, $current_depth + 1, $max_depth );
        }

        if ( is_object( $value ) ) {
            return '';
        }

        // if $value is NOT an array, it processes it HERE, EVEN IF $current_depth > $max_depth (because that check is at the top).
        // Wait, if $current_depth > $max_depth is at the top, it would return ''.
        // BUT $current_depth is 2 when it's passed from an array at depth 1.
        // 2 > 2 is FALSE.
        // So it continues.
        // It's not an array, so it skips the is_array block.
        // It does the expensive operations (wp_unslash, preg_replace, wp_kses).
        return 'processed: ' . substr($value, 0, 10);
    }
}
