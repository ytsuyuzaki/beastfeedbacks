<?php
/**
 * Tests for BeastFeedbacks_Public::register_beastfeedbacks_form().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Register_Beastfeedbacks_Form_Test extends BeastFeedbacks_TestCase {

	protected function set_up(): void {
		parent::set_up();

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
	}

	protected function tear_down(): void {
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] );
		$_POST    = array();
		$_REQUEST = array();

		parent::tear_down();
	}

	/**
	 * Verify that register_beastfeedbacks_form stores survey response and ignores control fields.
	 */
	public function test_register_beastfeedbacks_form_stores_survey_response_and_ignores_control_fields(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'survey parent',
			)
		);

		$_SERVER['REMOTE_ADDR']     = '192.0.2.10';
		$_SERVER['HTTP_USER_AGENT'] = 'Unit Test Agent';
		$_POST                      = $this->create_ajax_request(
			array(
				'Satisfaction' => 'Very satisfied',
				'Features'     => array( 'Speed', 'Support<script>' ),
			),
			$parent_id,
			'survey'
		);
		$_REQUEST                   = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertSame( 1, $response['success'] );
		$this->assertSame( 1, $response['count'] );
		$this->assertStringContainsString( 'questionnaire', $response['message'] );

		$stored = get_posts(
			array(
				'post_type'      => 'beastfeedbacks',
				'post_parent'    => $parent_id,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);

		$this->assertCount( 1, $stored );
		$this->created_ids[] = $stored[0]->ID;

		$content = json_decode( stripslashes( $stored[0]->post_content ), true );

		$this->assertSame( 'survey', $content['type'] );
		$this->assertSame( '192.0.2.10', $content['ip_address'] );
		$this->assertSame( 'Unit Test Agent', $content['user_agent'] );
		$this->assertSame( 'Very satisfied', $content['post_params']['Satisfaction'] );
		$this->assertSame( array( 'Speed', 'Support' ), $content['post_params']['Features'] );
		$this->assertArrayNotHasKey( 'action', $content['post_params'] );
		$this->assertArrayNotHasKey( '_wp_http_referer', $content['post_params'] );
		$this->assertSame( 'survey', get_post_meta( $stored[0]->ID, 'beastfeedbacks_type', true ) );
	}

	/**
	 * Verify that register_beastfeedbacks_form returns updated count for like response.
	 */
	public function test_register_beastfeedbacks_form_returns_updated_count_for_like_response(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'like parent',
			)
		);

		$this->create_like_post( $parent_id );

		$_SERVER['REMOTE_ADDR'] = '192.0.2.20';
		$_POST                  = $this->create_ajax_request( array(), $parent_id, 'like' );
		$_REQUEST               = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertSame( 1, $response['success'] );
		$this->assertSame( 2, $response['count'] );
		$this->assertStringContainsString( 'vote', $response['message'] );

		$stored = get_posts(
			array(
				'post_type'      => 'beastfeedbacks',
				'post_parent'    => $parent_id,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => 'beastfeedbacks_type',
				'meta_value'     => 'like',
			)
		);

		$this->assertCount( 2, $stored );
		foreach ( $stored as $post ) {
			$this->created_ids[] = $post->ID;
		}
	}

	/**
	 * Verify that register_beastfeedbacks_form fails when nonce is invalid or missing.
	 */
	public function test_register_beastfeedbacks_form_fails_when_nonce_is_invalid_or_missing(): void {
		$parent_id = $this->create_post();

		$_POST    = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'action'              => 'register_beastfeedbacks_form',
			'id'                  => (string) $parent_id,
			'beastfeedbacks_type' => 'like',
			'_wpnonce'            => 'invalid_nonce',
		);
		$_REQUEST = $_POST;

		$caught_exception = false;
		try {
			BeastFeedbacks_Public::get_instance()->register_beastfeedbacks_form();
		} catch ( WPAjaxDieStopException $e ) {
			$caught_exception = true;
			$this->assertSame( 403, $this->status );
		}

		$this->assertTrue( $caught_exception );
	}

	/**
	 * Verify that register_beastfeedbacks_form returns error response when post ID or feedback type is missing.
	 */
	public function test_register_beastfeedbacks_form_returns_error_when_id_or_type_is_missing(): void {
		$parent_id = $this->create_post();

		// Missing beastfeedbacks_type parameter.
		$_POST    = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'_ajax_nonce' => wp_create_nonce( 'register_beastfeedbacks_form' ),
			'action'      => 'register_beastfeedbacks_form',
			'id'          => (string) $parent_id,
		);
		$_REQUEST = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Invalid request', $response['data']['message'] );

		// Missing id parameter.
		$_POST    = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'_ajax_nonce'         => wp_create_nonce( 'register_beastfeedbacks_form' ),
			'action'              => 'register_beastfeedbacks_form',
			'beastfeedbacks_type' => 'like',
		);
		$_REQUEST = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Invalid request', $response['data']['message'] );
	}

	/**
	 * Verify that register_beastfeedbacks_form returns error response for invalid post ID.
	 */
	public function test_register_beastfeedbacks_form_returns_error_for_invalid_post_id(): void {
		// Non-existent post ID.
		$_POST    = $this->create_ajax_request( array(), 999999, 'like' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_REQUEST = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Invalid post ID', $response['data']['message'] );

		// Zero post ID.
		$_POST    = $this->create_ajax_request( array(), 0, 'like' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_REQUEST = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Invalid post ID', $response['data']['message'] );

		// Negative post ID.
		$_POST    = $this->create_ajax_request( array(), -1, 'like' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_REQUEST = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Invalid post ID', $response['data']['message'] );
	}

	/**
	 * Verify that register_beastfeedbacks_form returns error for invalid feedback type.
	 */
	public function test_register_beastfeedbacks_form_returns_error_for_invalid_feedback_type(): void {
		$parent_id = $this->create_post();

		// Unsupported string type.
		$_POST    = $this->create_ajax_request( array(), $parent_id, 'invalid_type' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_REQUEST = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Invalid feedback type', $response['data']['message'] );

		// Empty string type.
		$_POST    = $this->create_ajax_request( array(), $parent_id, '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_REQUEST = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Invalid request', $response['data']['message'] );
	}

	/**
	 * Verify that register_beastfeedbacks_form returns error response when saving feedback post fails.
	 */
	public function test_register_beastfeedbacks_form_returns_error_on_post_save_failure(): void {
		$parent_id = $this->create_post();

		$_POST    = $this->create_ajax_request( array(), $parent_id, 'like' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_REQUEST = $_POST;

		$fail_insert = static function () {
			return true;
		};
		add_filter( 'wp_insert_post_empty_content', $fail_insert );

		try {
			$response = $this->call_ajax_handler();
		} finally {
			remove_filter( 'wp_insert_post_empty_content', $fail_insert );
		}

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Failed to save feedback', $response['data']['message'] );
	}
}
