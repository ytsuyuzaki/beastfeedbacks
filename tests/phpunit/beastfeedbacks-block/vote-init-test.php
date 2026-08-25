<?php
/**
 * Tests for beastfeedbacks_block_vote_init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Vote_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function vote_init_registers_block(): void {
		if ( ! function_exists( 'beastfeedbacks_block_vote_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/vote/init.php';
		}

		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'beastfeedbacks/vote' ) ) {
			unregister_block_type( 'beastfeedbacks/vote' );
		}

		beastfeedbacks_block_vote_init();

		$registry = \WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'beastfeedbacks/vote' ) );
	}
}
