<?php
/**
 * Tests for BeastFeedbacks::get_instance().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Get_Instance_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function get_instance_returns_singleton(): void {
		$a = \BeastFeedbacks::get_instance();
		$b = \BeastFeedbacks::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks::class, $a );
		$this->assertSame( $a, $b, 'get_instance() must return the same instance' );
	}
}
