<?php
/**
 * Tests for beastfeedbacks_block_survey_input_init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Survey_Input_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function survey_input_init_registers_block(): void {
		if ( ! function_exists( 'beastfeedbacks_block_survey_input_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/survey-input/init.php';
		}

		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'beastfeedbacks/survey-input' ) ) {
			unregister_block_type( 'beastfeedbacks/survey-input' );
		}

		beastfeedbacks_block_survey_input_init();

		$registry = \WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'beastfeedbacks/survey-input' ) );
	}
}
