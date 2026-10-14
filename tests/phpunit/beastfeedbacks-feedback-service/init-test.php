<?php
/**
 * Tests for BeastFeedbacks_Feedback_Service instantiation.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Service_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function get_instance_returns_singleton(): void {
		$instance1 = \BeastFeedbacks_Feedback_Service::get_instance();
		$instance2 = \BeastFeedbacks_Feedback_Service::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Feedback_Service::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}
}
