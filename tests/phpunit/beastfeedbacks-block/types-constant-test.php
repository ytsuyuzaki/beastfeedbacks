<?php
/**
 * Tests for BeastFeedbacks_Block::TYPES constant.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Types_Constant_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function types_constant_has_expected_values(): void {
		$this->assertTrue( defined( '\BeastFeedbacks_Block::TYPES' ) );
		$this->assertSame(
			array( 'like', 'vote', 'survey' ),
			\BeastFeedbacks_Block::TYPES
		);
	}
}
