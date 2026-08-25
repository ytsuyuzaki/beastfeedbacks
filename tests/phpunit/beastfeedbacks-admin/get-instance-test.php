<?php
/**
 * Tests for BeastFeedbacks_Admin::get_instance().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Get_Instance_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function get_instance_returns_singleton(): void {
		$a = \BeastFeedbacks_Admin::get_instance();
		$b = \BeastFeedbacks_Admin::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Admin::class, $a );
		$this->assertSame( $a, $b );
	}
}
