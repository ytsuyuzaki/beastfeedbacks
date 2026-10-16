<?php
/**
 * Tests for BeastFeedbacks_Admin_List::get_instance().
 *
 * @package BeastFeedbacks
 */

/**
 * Test case for BeastFeedbacks_Admin_List::get_instance().
 */
class BeastFeedbacks_Admin_List_Get_Instance_Test extends BeastFeedbacks_TestCase {

	/**
	 * Test that get_instance returns the same instance.
	 *
	 * @test
	 */
	public function get_instance_returns_same_instance(): void {
		$instance1 = \BeastFeedbacks_Admin_List::get_instance();
		$instance2 = \BeastFeedbacks_Admin_List::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Admin_List::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}
}
