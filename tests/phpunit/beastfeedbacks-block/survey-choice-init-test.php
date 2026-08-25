<?php
/**
 * Tests for beastfeedbacks_block_survey_choice_init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Survey_Choice_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function survey_choice_init_registers_block_and_sets_script_translations(): void {
		$expected_dir = dirname( dirname( __DIR__ ) ) . '/src/survey-choice';

		if ( ! function_exists( 'beastfeedbacks_block_survey_choice_init' ) ) {
			require_once $expected_dir . '/init.php';
		}

		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'beastfeedbacks/survey-choice' ) ) {
			unregister_block_type( 'beastfeedbacks/survey-choice' );
		}

		beastfeedbacks_block_survey_choice_init();

		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'beastfeedbacks/survey-choice' ),
			'Block beastfeedbacks/survey-choice should be registered in WP_Block_Type_Registry'
		);
	}
}
