<?php
/**
 * Tests for BeastFeedbacks_Public::init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function init_registers_ajax_action_hooks(): void {
		$instance = \BeastFeedbacks_Public::get_instance();
		$instance->init();

		$this->assertNotFalse(
			has_action( 'wp_ajax_register_beastfeedbacks_form', array( $instance, 'register_beastfeedbacks_form' ) )
		);
		$this->assertNotFalse(
			has_action( 'wp_ajax_nopriv_register_beastfeedbacks_form', array( $instance, 'register_beastfeedbacks_form' ) )
		);
	}
}
