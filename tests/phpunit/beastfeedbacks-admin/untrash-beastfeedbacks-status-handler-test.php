<?php
/**
 * Tests for BeastFeedbacks_Admin::untrash_beastfeedbacks_status_handler().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Untrash_Beastfeedbacks_Status_Handler_Test extends BeastFeedbacks_TestCase {

	/**
	 * Test untrash_beastfeedbacks_status_handler returns previous status when post type is beastfeedbacks and previous status is publish.
	 *
	 * @test
	 */
	public function untrash_beastfeedbacks_status_handler_returns_previous_status_when_beastfeedbacks_and_previous_status_is_publish(): void {
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'trash',
				'post_title'  => 'Test BeastFeedback',
			)
		);

		$result = \BeastFeedbacks_Admin::get_instance()->untrash_beastfeedbacks_status_handler( 'draft', $post_id, 'publish' );
		$this->assertSame( 'publish', $result );
	}

	/**
	 * Test untrash_beastfeedbacks_status_handler returns publish when post type is beastfeedbacks and previous status is not publish.
	 *
	 * @test
	 */
	public function untrash_beastfeedbacks_status_handler_returns_publish_when_beastfeedbacks_and_previous_status_is_not_publish(): void {
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'trash',
				'post_title'  => 'Test BeastFeedback',
			)
		);

		$result = \BeastFeedbacks_Admin::get_instance()->untrash_beastfeedbacks_status_handler( 'draft', $post_id, 'draft' );
		$this->assertSame( 'publish', $result );
	}

	/**
	 * Test untrash_beastfeedbacks_status_handler returns current status when post type is not beastfeedbacks.
	 *
	 * @test
	 */
	public function untrash_beastfeedbacks_status_handler_returns_current_status_when_not_beastfeedbacks(): void {
		$post_id = $this->create_post(
			array(
				'post_status' => 'trash',
				'post_title'  => 'Test Standard Post',
			)
		);

		$result = \BeastFeedbacks_Admin::get_instance()->untrash_beastfeedbacks_status_handler( 'draft', $post_id, 'draft' );
		$this->assertSame( 'draft', $result );
	}
}
