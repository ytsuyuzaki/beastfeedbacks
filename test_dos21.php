<?php
// Let's test what happens to the PHPUnit tests if we remove recursion and reject multidimensional arrays.
// Currently:
// 			'nested_array'                  => array( 'level1' => array( 'level2' ) ),
// Returns:
// 		$this->assertSame( array( 'level1' => '' ), $result['nested_array'] );
// If we reject multidimensional arrays entirely, it would return `''` for the WHOLE `nested_array`.
// `assertArrayHasKey` might fail, or `assertSame( '', $result['nested_array'] )` would be needed.
// But the issue specifically mentions: "Mitigation possible by rejecting complex array structures entirely".
// So changing the behavior to reject the WHOLE array if it's complex is EXACTLY what the rationale suggests!
// It even explicitly says "entirely".
