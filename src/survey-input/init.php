<?php
/**
 * Survey-input.
 *
 * @package BeastFeedbacks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'beastfeedbacks_block_survey_input_init' ) ) {
	/**
	 * ブロック登録
	 */
	function beastfeedbacks_block_survey_input_init() {

		$type = register_block_type( __DIR__ );

		if ( $type && ! empty( $type->editor_script ) ) {
			wp_set_script_translations(
				$type->editor_script,
				BEASTFEEDBACKS_DOMAIN,
				BEASTFEEDBACKS_DIR . 'languages',
			);
		}
	}
}

beastfeedbacks_block_survey_input_init();
