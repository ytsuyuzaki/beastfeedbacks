<?php
// Let's reconsider the issue description:
// "Potential Denial of Service via Resource Exhaustion in sanitize_param_value"
// "Rationale: Mitigation possible by rejecting complex array structures entirely or flattening them before deep parsing."

// The issue might be related to deep parsing itself, or array recursion.
// But wait, the depth limit is hardcoded to 2 by default.
// Let's look at `extract_post_params` which calls `sanitize_param_value( $post_value );`
// The default depth is 1, max_depth is 2.
// That means arrays can only be 1 level deep.
// So how can it be "complex array structures"?
// Could it be that if someone passes a HUGE array, the recursion could be expensive?
// It limits items to 50: `$max_items = 50;`
// Wait, 50 items max. Depth 2 max.
// So max total items processed is 50?
// Actually, `extract_post_params` limits to 50 top-level parameters: `$max_params = (int) apply_filters( 'beastfeedbacks_max_post_params', 50 );`
// Then it calls `$this->sanitize_param_value( $post_value )` for each parameter.
// Inside `sanitize_param_value`, if it's an array, it iterates up to 50 items.
// So 50 top-level parameters * 50 items = 2500 items max.
// Is that enough for a DoS? Maybe if each item is a large string.
// But wait, what if we "reject complex array structures entirely"?
// If the application doesn't expect nested arrays at all in `$post_value`, we should just reject them or flatten them.
// Let's check `tests/phpunit/beastfeedbacks-public/extract-post-params-test.php`
