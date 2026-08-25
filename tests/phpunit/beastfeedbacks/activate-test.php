<?php
/**
 * Tests for beastfeedbacks_activate().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Activate_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function beastfeedbacks_activate_calls_activator_activate(): void {
		$dir = defined( 'BEASTFEEDBACKS_DIR' ) ? BEASTFEEDBACKS_DIR : dirname( __DIR__, 2 ) . '/';
		include_once $dir . 'includes/class-beastfeedbacks-activator.php';

		$called = false;
		$handle = \Patchwork\redefine(
			'BeastFeedbacks_Activator::activate',
			function () use ( &$called ) {
				$called = true;
			}
		);

		beastfeedbacks_activate();

		\Patchwork\restore( $handle );

		$this->assertTrue( $called, 'beastfeedbacks_activate() は BeastFeedbacks_Activator::activate() を呼び出すべき' );
	}
}
