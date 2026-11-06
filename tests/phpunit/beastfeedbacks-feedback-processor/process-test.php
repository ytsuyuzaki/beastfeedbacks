<?php
/**
 * Tests for BeastFeedbacks_Feedback_Processor::process().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Processor_Process_Test extends BeastFeedbacks_TestCase {

	/**
	 * Original remote address to restore after tests.
	 *
	 * @var string
	 */
	private $original_remote_addr = '';

	/**
	 * Setup before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->original_remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
	}

	/**
	 * Teardown after each test.
	 */
	public function tear_down(): void {
		$_SERVER['REMOTE_ADDR'] = $this->original_remote_addr;
		parent::tear_down();
	}

	/**
	 * Verify that process returns success for valid survey response.
	 */
	public function test_process_returns_success_for_survey_response(): void {
		$parent_id = $this->create_post();

		$post_data = array(
			'id'                  => (string) $parent_id,
			'beastfeedbacks_type' => 'survey',
			'question_1'          => 'answer_1',
		);

		$processor = \BeastFeedbacks_Feedback_Processor::get_instance();
		$response  = $processor->process( $post_data );

		$this->assertTrue( $response['success'] );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertArrayHasKey( 'message', $response['data'] );
		$this->assertStringContainsString( 'questionnaire', $response['data']['message'] );

		$stored = get_posts(
			array(
				'post_type'      => 'beastfeedbacks',
				'post_parent'    => $parent_id,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);
		$this->assertCount( 1, $stored );
		$this->created_ids[] = $stored[0]->ID;
	}

	/**
	 * Verify that process returns success and updated count for like response.
	 */
	public function test_process_returns_success_for_like_response(): void {
		$parent_id = $this->create_post();

		$post_data = array(
			'id'                  => (string) $parent_id,
			'beastfeedbacks_type' => 'like',
		);

		$processor = \BeastFeedbacks_Feedback_Processor::get_instance();
		$response  = $processor->process( $post_data );

		$this->assertTrue( $response['success'] );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertArrayHasKey( 'count', $response['data'] );
		$this->assertSame( 1, $response['data']['count'] );

		$stored = get_posts(
			array(
				'post_type'      => 'beastfeedbacks',
				'post_parent'    => $parent_id,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);
		$this->assertCount( 1, $stored );
		$this->created_ids[] = $stored[0]->ID;
	}

	/**
	 * Verify that process handles missing parameters.
	 */
	public function test_process_returns_error_for_missing_parameters(): void {
		$processor = \BeastFeedbacks_Feedback_Processor::get_instance();

		$post_data = array(
			'beastfeedbacks_type' => 'like',
		);
		$response = $processor->process( $post_data );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 400, $response['status_code'] );
		$this->assertSame( 'Invalid request', $response['data']['message'] );
	}

	/**
	 * Verify that process handles invalid type.
	 */
	public function test_process_returns_error_for_invalid_type(): void {
		$parent_id = $this->create_post();
		$processor = \BeastFeedbacks_Feedback_Processor::get_instance();

		$post_data = array(
			'id'                  => (string) $parent_id,
			'beastfeedbacks_type' => 'invalid_type',
		);
		$response = $processor->process( $post_data );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 400, $response['status_code'] );
		$this->assertSame( 'Invalid feedback type', $response['data']['message'] );
	}

	/**
	 * Verify that process handles invalid post ID.
	 */
	public function test_process_returns_error_for_invalid_post_id(): void {
		$processor = \BeastFeedbacks_Feedback_Processor::get_instance();

		$post_data = array(
			'id'                  => '9999999',
			'beastfeedbacks_type' => 'like',
		);
		$response = $processor->process( $post_data );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 400, $response['status_code'] );
		$this->assertSame( 'Invalid post ID', $response['data']['message'] );
	}

	/**
	 * Verify that process returns error when post save fails.
	 */
	public function test_process_returns_error_on_post_save_failure(): void {
		$parent_id = $this->create_post();

		$fail_insert = static function () {
			return true;
		};
		add_filter( 'wp_insert_post_empty_content', $fail_insert );

		$processor = \BeastFeedbacks_Feedback_Processor::get_instance();
		$post_data = array(
			'id'                  => (string) $parent_id,
			'beastfeedbacks_type' => 'like',
		);

		try {
			$response = $processor->process( $post_data );
		} finally {
			remove_filter( 'wp_insert_post_empty_content', $fail_insert );
		}

		$this->assertFalse( $response['success'] );
		$this->assertSame( 500, $response['status_code'] );
		$this->assertSame( 'Failed to save feedback', $response['data']['message'] );
	}

	/**
	 * Verify that process rate limits properly.
	 */
	public function test_process_enforces_rate_limiting(): void {
		$parent_id              = $this->create_post();
		$_SERVER['REMOTE_ADDR'] = '198.51.100.100';

		$processor = \BeastFeedbacks_Feedback_Processor::get_instance();
		$post_data = array(
			'id'                  => (string) $parent_id,
			'beastfeedbacks_type' => 'like',
		);

		for ( $i = 0; $i < 10; $i++ ) {
			$response = $processor->process( $post_data );
			$this->assertTrue( $response['success'] );
		}

		$response = $processor->process( $post_data );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 429, $response['status_code'] );
		$this->assertSame( 'Too many requests', $response['data']['message'] );
	}
}
