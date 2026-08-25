<?php
/**
 * Tests for beastfeedbacks_deactivate().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Deactivate_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function beastfeedbacks_deactivate_calls_deactivator_deactivate(): void {
		$dir = defined( 'BEASTFEEDBACKS_DIR' ) ? BEASTFEEDBACKS_DIR : dirname( __DIR__, 2 ) . '/';
		include_once $dir . 'includes/class-beastfeedbacks-deactivator.php';

		$called = false;
		$handle = \Patchwork\redefine(
			'BeastFeedbacks_Deactivator::deactivate',
			function () use ( &$called ) {
				$called = true;
			}
		);

		beastfeedbacks_deactivate();

		\Patchwork\restore( $handle );

		$this->assertTrue( $called, 'beastfeedbacks_deactivate() は BeastFeedbacks_Deactivator::deactivate() を呼び出すべき' );
	}
}
