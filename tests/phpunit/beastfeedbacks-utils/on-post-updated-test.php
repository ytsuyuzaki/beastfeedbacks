<?php
/**
 * Tests for BeastFeedbacks_Utils::on_post_updated().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Utils_On_Post_Updated_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function test_on_post_updated_returns_early_for_non_feedback_posts(): void {
		$parent_id = $this->create_post( array( 'post_title' => 'parent' ) );

		// Prime object cache for parent with a known value.
		wp_cache_set( 'like_count_' . $parent_id, 10, 'beastfeedbacks' );

		$post_before = get_post( $parent_id );
		wp_update_post(
			array(
				'ID'         => $parent_id,
				'post_title' => 'updated parent title',
			)
		);
		$post_after = get_post( $parent_id );

		// Call on_post_updated directly with standard post objects.
		\BeastFeedbacks_Utils::on_post_updated( $parent_id, $post_after, $post_before );

		// Verify cache was not invalidated or modified.
		$cached = wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' );
		$this->assertSame( 10, $cached );
	}

	/** @test */
	public function test_on_post_updated_handles_invalid_or_null_post_objects(): void {
		$parent_id = $this->create_post( array( 'post_title' => 'parent' ) );
		wp_cache_set( 'like_count_' . $parent_id, 5, 'beastfeedbacks' );

		// Test with null values.
		\BeastFeedbacks_Utils::on_post_updated( $parent_id, null, null );
		$this->assertSame( 5, wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );

		// Test with non-WP_Post stdClass objects.
		$dummy = (object) array( 'post_type' => 'beastfeedbacks' );
		\BeastFeedbacks_Utils::on_post_updated( $parent_id, $dummy, null );
		$this->assertSame( 5, wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' ) );
	}

	/** @test */
	public function test_on_post_updated_clears_former_and_new_parent_caches_on_parent_reassignment(): void {
		$parent_a = $this->create_post( array( 'post_title' => 'parent A' ) );
		$parent_b = $this->create_post( array( 'post_title' => 'parent B' ) );

		$like_id = $this->create_like_post( $parent_a );

		// Prime caches for both parents.
		wp_cache_set( 'like_count_' . $parent_a, 1, 'beastfeedbacks' );
		wp_cache_set( 'like_count_' . $parent_b, 0, 'beastfeedbacks' );

		$post_before = get_post( $like_id );

		// Create a simulated post_after object attached to parent B.
		$post_after              = clone $post_before;
		$post_after->post_parent = $parent_b;

		\BeastFeedbacks_Utils::on_post_updated( $like_id, $post_after, $post_before );

		// Both parent A and parent B cache keys should be cleared (return false on wp_cache_get).
		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_a, 'beastfeedbacks' ) );
		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_b, 'beastfeedbacks' ) );
	}

	/** @test */
	public function test_on_post_updated_clears_former_parent_cache_when_parent_detached(): void {
		$parent_a = $this->create_post( array( 'post_title' => 'parent A' ) );
		$like_id  = $this->create_like_post( $parent_a );

		wp_cache_set( 'like_count_' . $parent_a, 1, 'beastfeedbacks' );

		$post_before             = get_post( $like_id );
		$post_after              = clone $post_before;
		$post_after->post_parent = 0;

		\BeastFeedbacks_Utils::on_post_updated( $like_id, $post_after, $post_before );

		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_a, 'beastfeedbacks' ) );
	}

	/** @test */
	public function test_on_post_updated_clears_new_parent_cache_when_parent_attached(): void {
		$parent_b = $this->create_post( array( 'post_title' => 'parent B' ) );
		$like_id  = $this->create_like_post( 0 );

		wp_cache_set( 'like_count_' . $parent_b, 0, 'beastfeedbacks' );

		$post_before             = get_post( $like_id );
		$post_after              = clone $post_before;
		$post_after->post_parent = $parent_b;

		\BeastFeedbacks_Utils::on_post_updated( $like_id, $post_after, $post_before );

		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_b, 'beastfeedbacks' ) );
	}

	/** @test */
	public function test_on_post_updated_clears_cache_on_post_type_change_from_feedback(): void {
		$parent_a = $this->create_post( array( 'post_title' => 'parent A' ) );
		$like_id  = $this->create_like_post( $parent_a );

		wp_cache_set( 'like_count_' . $parent_a, 1, 'beastfeedbacks' );

		$post_before            = get_post( $like_id );
		$post_after             = clone $post_before;
		$post_after->post_type = 'post';

		\BeastFeedbacks_Utils::on_post_updated( $like_id, $post_after, $post_before );

		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_a, 'beastfeedbacks' ) );
	}

	/** @test */
	public function test_on_post_updated_clears_cache_on_post_type_change_to_feedback(): void {
		$parent_b = $this->create_post( array( 'post_title' => 'parent B' ) );

		$post_id = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_parent' => $parent_b,
			)
		);

		wp_cache_set( 'like_count_' . $parent_b, 0, 'beastfeedbacks' );

		$post_before            = get_post( $post_id );
		$post_after             = clone $post_before;
		$post_after->post_type = 'beastfeedbacks';

		\BeastFeedbacks_Utils::on_post_updated( $post_id, $post_after, $post_before );

		$this->assertFalse( wp_cache_get( 'like_count_' . $parent_b, 'beastfeedbacks' ) );
	}

	/** @test */
	public function test_post_updated_hook_triggers_cache_invalidation(): void {
		\BeastFeedbacks_Utils::init();

		$parent_a = $this->create_post( array( 'post_title' => 'parent A' ) );
		$parent_b = $this->create_post( array( 'post_title' => 'parent B' ) );

		$like_id = $this->create_like_post( $parent_a );

		// Prime object cache for both parents.
		$this->assertSame( 1, \BeastFeedbacks_Utils::get_like_count( $parent_a ) );
		$this->assertSame( 0, \BeastFeedbacks_Utils::get_like_count( $parent_b ) );

		// Update post parent using wp_update_post, which fires the post_updated action hook.
		wp_update_post(
			array(
				'ID'          => $like_id,
				'post_parent' => $parent_b,
			)
		);

		// Caches for both parents should be invalidated and recalculated accurately.
		$this->assertSame( 0, \BeastFeedbacks_Utils::get_like_count( $parent_a ) );
		$this->assertSame( 1, \BeastFeedbacks_Utils::get_like_count( $parent_b ) );
	}
}
