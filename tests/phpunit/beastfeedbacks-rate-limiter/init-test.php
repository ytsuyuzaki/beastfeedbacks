<?php
/**
 * Tests for BeastFeedbacks_Rate_Limiter instantiation.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Rate_Limiter_Init_Test extends BeastFeedbacks_TestCase {
	/**
	 * Test get_instance returns the same instance.
	 */
	public function test_get_instance(): void {
		$instance1 = \BeastFeedbacks_Rate_Limiter::get_instance();
		$instance2 = \BeastFeedbacks_Rate_Limiter::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Rate_Limiter::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}
}
