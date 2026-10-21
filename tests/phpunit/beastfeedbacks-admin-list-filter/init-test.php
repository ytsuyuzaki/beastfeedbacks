<?php
/**
 * Tests for BeastFeedbacks_Admin_List_Filter::init().
 *
 * @package BeastFeedbacks
 */

/**
 * Test case for BeastFeedbacks_Admin_List_Filter::init().
 */
class BeastFeedbacks_Admin_List_Filter_Init_Test extends BeastFeedbacks_TestCase {

	/**
	 * Tear down method to clean up hooks.
	 *
	 * @return void
	 */
	protected function tear_down(): void {
		parent::tear_down();
		$this->remove_all_filters();
	}

	/**
	 * Test that init adds the required actions and filters for the admin list filter.
	 *
	 * @test
	 */
	public function init_adds_actions_and_filters(): void {
		$filter = \BeastFeedbacks_Admin_List_Filter::get_instance();
		$filter->init();

		$this->assertTrue( has_action( 'restrict_manage_posts', array( $filter, 'add_type_filter' ) ) !== false );
		$this->assertTrue( has_action( 'restrict_manage_posts', array( $filter, 'add_source_filter' ) ) !== false );

		$this->assertTrue( has_action( 'pre_get_posts', array( $filter, 'type_filter_result' ) ) !== false );
		$this->assertTrue( has_action( 'pre_get_posts', array( $filter, 'source_filter_result' ) ) !== false );
		$this->assertTrue( has_filter( 'the_posts', array( $filter, 'prime_parent_post_caches' ) ) !== false );

		$this->assertTrue( has_action( 'save_post_beastfeedbacks', array( $filter, 'clear_source_filter_cache' ) ) !== false );
		$this->assertTrue( has_action( 'deleted_post', array( $filter, 'clear_source_filter_cache' ) ) !== false );
		$this->assertTrue( has_action( 'trashed_post', array( $filter, 'clear_source_filter_cache' ) ) !== false );
		$this->assertTrue( has_action( 'untrashed_post', array( $filter, 'clear_source_filter_cache' ) ) !== false );
	}
}
