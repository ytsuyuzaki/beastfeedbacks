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
	 * @dataProvider previous_non_publish_statuses_provider
	 *
	 * @param string $previous_status The previous status before trashing.
	 */
	public function untrash_beastfeedbacks_status_handler_returns_publish_when_beastfeedbacks_and_previous_status_is_not_publish( string $previous_status ): void {
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'trash',
				'post_title'  => 'Test BeastFeedback',
			)
		);

		$result = \BeastFeedbacks_Admin::get_instance()->untrash_beastfeedbacks_status_handler( 'draft', $post_id, $previous_status );
		$this->assertSame( 'publish', $result );
	}

	/**
	 * Data provider for non-publish previous statuses.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function previous_non_publish_statuses_provider(): array {
		return array(
			'draft status'   => array( 'draft' ),
			'pending status' => array( 'pending' ),
			'private status' => array( 'private' ),
			'future status'  => array( 'future' ),
		);
	}

	/**
	 * Test untrash_beastfeedbacks_status_handler returns current status when post type is not beastfeedbacks.
	 *
	 * @test
	 * @dataProvider standard_post_types_provider
	 *
	 * @param string $post_type Post type to test.
	 */
	public function untrash_beastfeedbacks_status_handler_returns_current_status_when_not_beastfeedbacks( string $post_type ): void {
		$post_id = $this->create_post(
			array(
				'post_type'   => $post_type,
				'post_status' => 'trash',
				'post_title'  => 'Test Standard Post',
			)
		);

		$result = \BeastFeedbacks_Admin::get_instance()->untrash_beastfeedbacks_status_handler( 'draft', $post_id, 'draft' );
		$this->assertSame( 'draft', $result );
	}

	/**
	 * Data provider for standard non-beastfeedbacks post types.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function standard_post_types_provider(): array {
		return array(
			'post post type' => array( 'post' ),
			'page post type' => array( 'page' ),
		);
	}

	/**
	 * Test untrash_beastfeedbacks_status_handler via WordPress apply_filters.
	 *
	 * @test
	 */
	public function untrash_beastfeedbacks_status_handler_works_via_apply_filters(): void {
		$instance = \BeastFeedbacks_Admin::get_instance();
		$instance->init();

		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'trash',
				'post_title'  => 'Test BeastFeedback',
			)
		);

		$filtered_status = apply_filters( 'wp_untrash_post_status', 'draft', $post_id, 'draft' );
		$this->assertSame( 'publish', $filtered_status );
	}
}
