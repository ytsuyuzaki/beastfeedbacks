<?php
/**
 * Tests for BeastFeedbacks_Source_Repository::get_instance().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Source_Repository_Get_Instance_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function get_instance_returns_same_instance(): void {
		$instance1 = \BeastFeedbacks_Source_Repository::get_instance();
		$instance2 = \BeastFeedbacks_Source_Repository::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Source_Repository::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}
}
