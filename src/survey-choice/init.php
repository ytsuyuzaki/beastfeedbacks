<?php
/**
 * Survey-choice.
 *
 * @package BeastFeedbacks
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ブロック登録
 */
function beastfeedbacks_block_survey_choice_init() {

	$type = register_block_type( __DIR__ );

	wp_set_script_translations(
		$type->editor_script,
		BEASTFEEDBACKS_DOMAIN,
		BEASTFEEDBACKS_DIR . 'languages',
	);
}

beastfeedbacks_block_survey_choice_init();
