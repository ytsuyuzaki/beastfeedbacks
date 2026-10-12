<?php
/**
 * Tests for BeastFeedbacks_Export::init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Export_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function init_registers_ajax_action_with_default_name(): void {
		$instance = \BeastFeedbacks_Export::get_instance();
		$instance->init();

		$this->assertSame(
			10,
			has_action( 'wp_ajax_beastfeedbacks_export', array( $instance, 'download_csv' ) )
		);
	}

	/** @test */
	public function init_registers_ajax_action_with_custom_name(): void {
		$instance = \BeastFeedbacks_Export::get_instance();
		$instance->init( 'custom_export_action' );

		$this->assertSame(
			10,
			has_action( 'wp_ajax_custom_export_action', array( $instance, 'download_csv' ) )
		);
	}
}
