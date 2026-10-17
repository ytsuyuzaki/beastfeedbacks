<?php
/**
 * Tests for BeastFeedbacks_Feedback_Service::build_response_data.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Service_Build_Response_Data_Test extends BeastFeedbacks_TestCase {

	/**
	 * Service instance.
	 *
	 * @var BeastFeedbacks_Feedback_Service
	 */
	private $service;

	/**
	 * Set up test environment.
	 */
	protected function set_up(): void {
		parent::set_up();
		$this->service = BeastFeedbacks_Feedback_Service::get_instance();
	}

	/**
	 * Test response data for 'survey' type.
	 *
	 * @test
	 */
	public function test_build_response_data_survey(): void {
		$post_id = $this->create_post();
		$type    = 'survey';

		$response = $this->service->build_response_data( $post_id, $type );

		$this->assertIsArray( $response );
		$this->assertSame( 1, $response['success'] );
		$this->assertSame( 'Thank you for your responses to the questionnaire. ', $response['message'] );
		$this->assertSame( 1, $response['count'] );
	}

	/**
	 * Test response data for 'vote' type.
	 *
	 * @test
	 */
	public function test_build_response_data_vote(): void {
		$post_id = $this->create_post();
		$type    = 'vote';

		$response = $this->service->build_response_data( $post_id, $type );

		$this->assertIsArray( $response );
		$this->assertSame( 1, $response['success'] );
		$this->assertSame( 'Thank you for the vote. ', $response['message'] );
		$this->assertSame( 1, $response['count'] );
	}

	/**
	 * Test response data for 'like' type.
	 *
	 * @test
	 */
	public function test_build_response_data_like(): void {
		$post_id = $this->create_post();
		$type    = 'like';

		// Initially, the like count should be 0.
		$response_initial = $this->service->build_response_data( $post_id, $type );
		$this->assertIsArray( $response_initial );
		$this->assertSame( 1, $response_initial['success'] );
		$this->assertSame( 'Thank you for the vote. ', $response_initial['message'] );
		$this->assertSame( 0, $response_initial['count'] );

		// Create 2 like posts.
		$this->create_like_post( $post_id );
		$this->create_like_post( $post_id );

		// Clear cache to get fresh count, although clear_like_count_cache should be triggered on post creation if hooks run,
		// but in test environment hooks might need explicit call or we can manually clear the cache for safety if needed,
		// wait, wp_insert_post triggers hooks. Let's rely on it.
		// Well, actually I need to clear cache explicitly if I am bypassing hooks or in case object cache persists.
		// BeastFeedbacks_Utils::clear_like_count_cache triggers on save_post_beastfeedbacks which wp_insert_post calls.

		$response = $this->service->build_response_data( $post_id, $type );

		$this->assertIsArray( $response );
		$this->assertSame( 1, $response['success'] );
		$this->assertSame( 'Thank you for the vote. ', $response['message'] );
		$this->assertSame( 2, $response['count'] );
	}
}
