<?php
/**
 * Tests for BeastFeedbacks_Admin::clear_source_filter_cache().
 *
 * @package BeastFeedbacks
 */

/**
 * Test case for BeastFeedbacks_Admin::clear_source_filter_cache().
 */
class BeastFeedbacks_Admin_Clear_Source_Filter_Cache_Test extends BeastFeedbacks_TestCase {

	/**
	 * Test that clear_source_filter_cache clears cache when passed a WP_Post object of target post type.
	 *
	 * @test
	 */
	public function clear_source_filter_cache_clears_cache_with_target_post_object(): void {
		$post_id = $this->create_post(
			array(
				'post_type' => 'beastfeedbacks',
			)
		);
		$post    = get_post( $post_id );

		wp_cache_set( 'source_filter_parent_ids', array( 100, 200 ), 'beastfeedbacks' );
		$this->assertSame( array( 100, 200 ), wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		$admin = \BeastFeedbacks_Admin::get_instance();
		$admin->clear_source_filter_cache( $post_id, $post );

		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/**
	 * Test that clear_source_filter_cache returns early and leaves cache intact when passed a WP_Post object of non-matching post type.
	 *
	 * @test
	 */
	public function clear_source_filter_cache_ignores_non_target_post_object(): void {
		$post_id = $this->create_post(
			array(
				'post_type' => 'post',
			)
		);
		$post    = get_post( $post_id );

		wp_cache_set( 'source_filter_parent_ids', array( 100, 200 ), 'beastfeedbacks' );

		$admin = \BeastFeedbacks_Admin::get_instance();
		$admin->clear_source_filter_cache( $post_id, $post );

		$this->assertSame( array( 100, 200 ), wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/**
	 * Test that clear_source_filter_cache clears cache when passed a post ID of target post type without a post object.
	 *
	 * @test
	 */
	public function clear_source_filter_cache_clears_cache_with_target_post_id(): void {
		$post_id = $this->create_post(
			array(
				'post_type' => 'beastfeedbacks',
			)
		);

		wp_cache_set( 'source_filter_parent_ids', array( 300, 400 ), 'beastfeedbacks' );

		$admin = \BeastFeedbacks_Admin::get_instance();
		$admin->clear_source_filter_cache( $post_id, null );

		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/**
	 * Test that clear_source_filter_cache returns early and leaves cache intact when passed a post ID of non-matching post type without a post object.
	 *
	 * @test
	 */
	public function clear_source_filter_cache_ignores_non_target_post_id(): void {
		$post_id = $this->create_post(
			array(
				'post_type' => 'page',
			)
		);

		wp_cache_set( 'source_filter_parent_ids', array( 300, 400 ), 'beastfeedbacks' );

		$admin = \BeastFeedbacks_Admin::get_instance();
		$admin->clear_source_filter_cache( $post_id, null );

		$this->assertSame( array( 300, 400 ), wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/**
	 * Test that clear_source_filter_cache clears cache when called with default/empty arguments.
	 *
	 * @test
	 */
	public function clear_source_filter_cache_clears_cache_with_default_arguments(): void {
		wp_cache_set( 'source_filter_parent_ids', array( 500 ), 'beastfeedbacks' );

		$admin = \BeastFeedbacks_Admin::get_instance();
		$admin->clear_source_filter_cache();

		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}
}
