<?php
/**
 * Tests for beastfeedbacks_block_survey_form_init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Survey_Form_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function survey_form_init_registers_block(): void {
		if ( ! function_exists( 'beastfeedbacks_block_survey_form_init' ) ) {
			require_once BEASTFEEDBACKS_DIR . 'src/survey-form/init.php';
		}

		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'beastfeedbacks/survey-form' ) ) {
			unregister_block_type( 'beastfeedbacks/survey-form' );
		}

		beastfeedbacks_block_survey_form_init();

		$registry = \WP_Block_Type_Registry::get_instance();
		$this->assertTrue( $registry->is_registered( 'beastfeedbacks/survey-form' ) );
	}
}
