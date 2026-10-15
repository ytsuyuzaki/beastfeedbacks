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
	public function tear_down() {
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

		$this->assertTrue( has_action( 'restrict_manage_posts', array( $admin_list, 'add_type_filter' ) ) !== false );
		$this->assertTrue( has_action( 'restrict_manage_posts', array( $admin_list, 'add_source_filter' ) ) !== false );

		$this->assertTrue( has_action( 'pre_get_posts', array( $admin_list, 'type_filter_result' ) ) !== false );
		$this->assertTrue( has_action( 'pre_get_posts', array( $admin_list, 'source_filter_result' ) ) !== false );
		$this->assertTrue( has_filter( 'the_posts', array( $admin_list, 'prime_parent_post_caches' ) ) !== false );

		$this->assertTrue( has_action( 'save_post_beastfeedbacks', array( $admin_list, 'clear_source_filter_cache' ) ) !== false );
		$this->assertTrue( has_action( 'deleted_post', array( $admin_list, 'clear_source_filter_cache' ) ) !== false );
		$this->assertTrue( has_action( 'trashed_post', array( $admin_list, 'clear_source_filter_cache' ) ) !== false );
		$this->assertTrue( has_action( 'untrashed_post', array( $admin_list, 'clear_source_filter_cache' ) ) !== false );
	}
}
