<?php
/**
 * Tests for BeastFeedbacks_Block::get_instance().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Get_Instance_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function get_instance_returns_singleton(): void {
		$a = \BeastFeedbacks_Block::get_instance();
		$b = \BeastFeedbacks_Block::get_instance();
		$this->assertInstanceOf( \BeastFeedbacks_Block::class, $a );
		$this->assertSame( $a, $b, 'get_instance() must return the same instance' );
	}
}
