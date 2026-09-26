<?php
/**
 * Tests for BeastFeedbacks_Utils::clear_like_count_cache().
 *
 * @package BeastFeedbacks
 */

/**
 * Test class for BeastFeedbacks_Utils::clear_like_count_cache().
 */
class BeastFeedbacks_Utils_Clear_Like_Count_Cache_Test extends BeastFeedbacks_TestCase {

	/**
	 * Test clearing cache using post ID.
	 *
	 * @test
	 */
	public function clear_like_count_cache_with_post_id_clears_parent_cache(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Post',
			)
		);

		$like_id = $this->create_like_post( $parent_id );

		// Prime cache.
		wp_cache_set( 'like_count_' . $parent_id, 5, 'beastfeedbacks' );
		$this->assertSame( 5, wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );

		// Clear cache passing only post ID.
		\BeastFeedbacks_Utils::clear_like_count_cache( $like_id );

		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );
	}

	/**
	 * Test clearing cache using WP_Post object.
	 *
	 * @test
	 */
	public function clear_like_count_cache_with_post_object_clears_parent_cache(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Post',
			)
		);

		$like_id     = $this->create_like_post( $parent_id );
		$post_object = get_post( $like_id );

		// Prime cache.
		wp_cache_set( 'like_count_' . $parent_id, 10, 'beastfeedbacks' );
		$this->assertSame( 10, wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );

		// Clear cache passing post object.
		\BeastFeedbacks_Utils::clear_like_count_cache( $like_id, $post_object );

		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );
	}

	/**
	 * Test that calling clear_like_count_cache with invalid post ID does not clear parent cache.
	 *
	 * @test
	 */
	public function clear_like_count_cache_with_invalid_post_id_does_not_clear_cache(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Post',
			)
		);

		// Prime cache.
		wp_cache_set( 'like_count_' . $parent_id, 3, 'beastfeedbacks' );

		// Call clear_like_count_cache with non-existent post ID.
		\BeastFeedbacks_Utils::clear_like_count_cache( 99999 );

		// Cache should remain intact.
		$this->assertSame( 3, wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );
	}

	/**
	 * Test that calling clear_like_count_cache with non-beastfeedbacks post type does not clear cache.
	 *
	 * @test
	 */
	public function clear_like_count_cache_with_non_beastfeedbacks_post_type_does_not_clear_cache(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Post',
			)
		);

		$standard_post_id = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_parent' => $parent_id,
			)
		);

		// Prime cache.
		wp_cache_set( 'like_count_' . $parent_id, 7, 'beastfeedbacks' );

		// Call clear_like_count_cache for standard post.
		\BeastFeedbacks_Utils::clear_like_count_cache( $standard_post_id );

		// Cache should remain intact.
		$this->assertSame( 7, wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );
	}

	/**
	 * Test that calling clear_like_count_cache with no post parent does not clear cache.
	 *
	 * @test
	 */
	public function clear_like_count_cache_with_no_post_parent_does_not_clear_cache(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Post',
			)
		);

		$orphaned_like_id = $this->create_like_post( 0 );

		// Prime cache.
		wp_cache_set( 'like_count_' . $parent_id, 4, 'beastfeedbacks' );

		// Call clear_like_count_cache for orphaned like post.
		\BeastFeedbacks_Utils::clear_like_count_cache( $orphaned_like_id );

		// Cache should remain intact.
		$this->assertSame( 4, wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );
	}

	/**
	 * Test that calling clear_like_count_cache with invalid object type falls back to get_post.
	 *
	 * @test
	 */
	public function clear_like_count_cache_falls_back_to_get_post_when_post_param_is_invalid_type(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Post',
			)
		);

		$like_id = $this->create_like_post( $parent_id );

		// Prime cache.
		wp_cache_set( 'like_count_' . $parent_id, 8, 'beastfeedbacks' );

		// Pass non-WP_Post object as second param.
		$invalid_post_param = (object) array( 'ID' => 123 );
		\BeastFeedbacks_Utils::clear_like_count_cache( $like_id, $invalid_post_param );

		// Cache should be cleared because it falls back to get_post($like_id).
		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );
	}
}
