<?php
/**
 * Tests for BeastFeedbacks_Admin::init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Init_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function init_registers_admin_enqueue_scripts_action(): void {
		$instance = \BeastFeedbacks_Admin::get_instance();
		$instance->init();

		$this->assertSame(
			10,
			has_action( 'admin_enqueue_scripts', array( $instance, 'admin_enqueue_scripts' ) )
		);
	}

	/** @test */
	public function init_registers_untrash_beastfeedbacks_status_handler_filter(): void {
		$instance = \BeastFeedbacks_Admin::get_instance();
		$instance->init();

		$this->assertSame(
			10,
			has_filter( 'wp_untrash_post_status', array( $instance, 'untrash_beastfeedbacks_status_handler' ) )
		);
	}

	/** @test */
	public function init_registers_export_action_with_custom_name(): void {
		$instance = \BeastFeedbacks_Admin::get_instance();
		$original_action_name = $instance->export_action_name;

		$instance->export_action_name = 'custom_admin_export_action';
		$instance->init();

		$this->assertSame(
			10,
			has_action( 'wp_ajax_custom_admin_export_action', array( \BeastFeedbacks_Export::get_instance(), 'download_csv' ) )
		);

		// Restore original name.
		$instance->export_action_name = $original_action_name;
	}
}
