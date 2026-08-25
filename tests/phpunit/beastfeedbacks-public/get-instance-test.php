<?php
/**
 * Tests for BeastFeedbacks_Public::get_instance().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Get_Instance_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function get_instance_returns_singleton(): void {
		$a = \BeastFeedbacks_Public::get_instance();
		$b = \BeastFeedbacks_Public::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Public::class, $a );
		$this->assertSame( $a, $b );
	}
}
