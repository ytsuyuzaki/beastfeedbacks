<?php
/**
 * Tests for beastfeedbacks_block_like_init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Like_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function like_init_registers_block(): void {
		if ( ! function_exists( 'beastfeedbacks_block_like_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/like/init.php';
		}

		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'beastfeedbacks/like' ) ) {
			unregister_block_type( 'beastfeedbacks/like' );
		}

		beastfeedbacks_block_like_init();

		$registry = \WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'beastfeedbacks/like' ) );
	}
}
