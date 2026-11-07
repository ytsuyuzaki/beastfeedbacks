<?php
/**
 * Tests for BeastFeedbacks_Source_Repository::clear_cache().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Source_Repository_Clear_Cache_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		wp_cache_delete( 'source_filter_parent_ids', 'beastfeedbacks' );
		parent::tear_down();
	}

	/** @test */
	public function clear_cache_deletes_cache_when_valid_post_object_is_passed(): void {
		wp_cache_set( 'source_filter_parent_ids', array( 123 ), 'beastfeedbacks' );

		$post = (object) array( 'post_type' => 'beastfeedbacks' );
		$post = new WP_Post( $post );

		\BeastFeedbacks_Source_Repository::get_instance()->clear_cache( 0, $post );

		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/** @test */
	public function clear_cache_deletes_cache_when_valid_post_id_is_passed(): void {
		wp_cache_set( 'source_filter_parent_ids', array( 123 ), 'beastfeedbacks' );

		$post_id = $this->create_post(
			array(
				'post_title' => 'Feedback Post',
				'post_type'  => 'beastfeedbacks',
			)
		);

		\BeastFeedbacks_Source_Repository::get_instance()->clear_cache( $post_id, null );

		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/** @test */
	public function clear_cache_does_not_delete_cache_for_invalid_post_type(): void {
		wp_cache_set( 'source_filter_parent_ids', array( 123 ), 'beastfeedbacks' );

		$post = clone get_post( $this->create_post( array( 'post_type' => 'post' ) ) );

		\BeastFeedbacks_Source_Repository::get_instance()->clear_cache( 0, $post );

		$this->assertSame( array( 123 ), wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/** @test */
	public function clear_cache_deletes_cache_when_neither_post_nor_post_id_is_passed(): void {
		wp_cache_set( 'source_filter_parent_ids', array( 123 ), 'beastfeedbacks' );
		\BeastFeedbacks_Source_Repository::get_instance()->clear_cache();
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}
}
