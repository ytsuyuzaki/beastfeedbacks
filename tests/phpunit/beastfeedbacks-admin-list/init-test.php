<?php
/**
 * Tests for BeastFeedbacks_Admin_List::init().
 *
 * @package BeastFeedbacks
 */

/**
 * Test case for BeastFeedbacks_Admin_List::init().
 */
class BeastFeedbacks_Admin_List_Init_Test extends BeastFeedbacks_TestCase {

	/**
	 * Tear down method to unregister blocks and scripts.
	 *
	 * @return void
	 */
	protected function tear_down(): void {
		parent::tear_down();
		$this->remove_all_filters();
	}

	/**
	 * Test that init adds the required actions and filters for the admin list table.
	 *
	 * @test
	 */
	public function init_adds_actions_and_filters(): void {
		$admin_list = \BeastFeedbacks_Admin_List::get_instance();
		$admin_list->init();

		$this->assertTrue( has_filter( 'post_row_actions', array( $admin_list, 'manage_post_row_actions' ) ) !== false );
		$this->assertTrue( has_filter( 'wp_untrash_post_status', array( $admin_list, 'untrash_beastfeedbacks_status_handler' ) ) !== false );

		$this->assertTrue( has_filter( 'manage_beastfeedbacks_posts_columns', array( $admin_list, 'manage_posts_columns' ) ) !== false );
		$this->assertTrue( has_action( 'manage_posts_custom_column', array( $admin_list, 'manage_posts_custom_column' ) ) !== false );
	}
}
