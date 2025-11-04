<?php

use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BeastFeedbacksTest extends TestCase {


	/**
	 * @test
	 */
	public function get_like_count() {
		$count = BeastFeedbacks::get_instance()->get_like_count( 1 );
		$this->assertSame( $count, 0 );
	}
}
