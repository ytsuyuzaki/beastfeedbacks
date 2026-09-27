<?php
/**
 * Tests for BeastFeedbacks_Public::is_valid_target_post().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Is_Valid_Target_Post_Test extends BeastFeedbacks_TestCase {

	/**
	 * Verify that is_valid_target_post returns false for zero or negative post IDs.
	 */
	public function test_is_valid_target_post_returns_false_for_invalid_ids(): void {
		$public = BeastFeedbacks_Public::get_instance();

		$this->assertFalse( $public->is_valid_target_post( 0 ) );
		$this->assertFalse( $public->is_valid_target_post( -1 ) );
		$this->assertFalse( $public->is_valid_target_post( -100 ) );
	}

	/**
	 * Verify that is_valid_target_post returns false for non-existent post IDs.
	 */
	public function test_is_valid_target_post_returns_false_for_non_existent_post(): void {
		$public = BeastFeedbacks_Public::get_instance();

		$this->assertFalse( $public->is_valid_target_post( 999999 ) );
	}

	/**
	 * Verify that is_valid_target_post returns false for unpublished post statuses (draft, trash).
	 */
	public function test_is_valid_target_post_returns_false_for_unpublished_posts(): void {
		$public = BeastFeedbacks_Public::get_instance();

		$draft_id = $this->create_post(
			array(
				'post_status' => 'draft',
			)
		);
		$this->assertFalse( $public->is_valid_target_post( $draft_id ) );

		$trash_id = $this->create_post(
			array(
				'post_status' => 'trash',
			)
		);
		$this->assertFalse( $public->is_valid_target_post( $trash_id ) );
	}

	/**
	 * Verify that is_valid_target_post returns false when target post is itself a feedback item.
	 */
	public function test_is_valid_target_post_returns_false_for_beastfeedbacks_post_type(): void {
		$public      = BeastFeedbacks_Public::get_instance();
		$parent_id   = $this->create_post();
		$feedback_id = $this->create_like_post( $parent_id );

		$this->assertFalse( $public->is_valid_target_post( $feedback_id ) );
	}

	/**
	 * Verify that is_valid_target_post returns true for a valid, published target post.
	 */
	public function test_is_valid_target_post_returns_true_for_valid_published_post(): void {
		$public  = BeastFeedbacks_Public::get_instance();
		$post_id = $this->create_post(
			array(
				'post_status' => 'publish',
			)
		);

		$this->assertTrue( $public->is_valid_target_post( $post_id ) );
	}
}
