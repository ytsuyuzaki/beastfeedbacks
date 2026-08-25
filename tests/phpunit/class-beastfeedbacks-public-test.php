<?php
/**
 * Tests for BeastFeedbacks_Public class.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Test extends BeastFeedbacks_TestCase {

	protected function set_up(): void {
		parent::set_up();

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
	}

	protected function tear_down(): void {
		// グローバルの後片付け
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] );
		$_POST    = array();
		$_REQUEST = array();

		parent::tear_down();
	}

	/** @test */
	public function register_beastfeedbacks_form_stores_survey_response_and_ignores_control_fields(): void {
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

	/** @test */
	public function register_beastfeedbacks_form_returns_updated_count_for_like_response(): void {
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

	/** @test */
	public function get_instance_returns_singleton(): void {
		$a = \BeastFeedbacks_Public::get_instance();
		$b = \BeastFeedbacks_Public::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Public::class, $a );
		$this->assertSame( $a, $b );
	}

	/** @test */
	public function get_user_agent_returns_empty_when_not_set(): void {
		unset( $_SERVER['HTTP_USER_AGENT'] );
		$ua = \BeastFeedbacks_Public::get_instance()->get_user_agent();
		$this->assertSame( '', $ua );
	}

	/** @test */
	public function get_user_agent_returns_sanitized_value_when_set(): void {
		$_SERVER['HTTP_USER_AGENT'] = "TestAgent/1.0\t";
		$ua                         = \BeastFeedbacks_Public::get_instance()->get_user_agent();
		$this->assertStringContainsString( 'TestAgent/1.0', $ua );
	}

	/** @test */
	public function get_ip_address_returns_value_when_set(): void {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$ip                     = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame( '127.0.0.1', $ip );
	}

	/** @test */
	public function init_registers_ajax_action_hooks(): void {
		$instance = \BeastFeedbacks_Public::get_instance();
		$instance->init();

		$this->assertNotFalse(
			has_action( 'wp_ajax_register_beastfeedbacks_form', array( $instance, 'register_beastfeedbacks_form' ) )
		);
		$this->assertNotFalse(
			has_action( 'wp_ajax_nopriv_register_beastfeedbacks_form', array( $instance, 'register_beastfeedbacks_form' ) )
		);
	}

	/** @test */
	public function extract_post_params_filters_and_sanitizes_input(): void {
		$raw_data = array(
			'id'                  => '123',
			'beastfeedbacks_type' => 'survey',
			'action'              => 'register_beastfeedbacks_form',
			'_wp_http_referer'    => '/test',
			'_wpnonce'            => 'nonce123',
			'comment'             => ' Great service! <script>alert(1)</script> ',
			'tags'                => array( 'fast', 'helpful<script>' ),
		);

		$result = \BeastFeedbacks_Public::get_instance()->extract_post_params( $raw_data );

		$this->assertArrayNotHasKey( 'id', $result );
		$this->assertArrayNotHasKey( 'beastfeedbacks_type', $result );
		$this->assertArrayNotHasKey( 'action', $result );
		$this->assertArrayNotHasKey( '_wp_http_referer', $result );
		$this->assertArrayNotHasKey( '_wpnonce', $result );
		$this->assertSame( 'Great service!', $result['comment'] );
		$this->assertSame( array( 'fast', 'helpful' ), $result['tags'] );
	}

	/** @test */
	public function format_feedback_content_encodes_json_correctly(): void {
		$content = \BeastFeedbacks_Public::get_instance()->format_feedback_content(
			'TestUA',
			'127.0.0.1',
			'survey',
			array( 'key' => 'val' )
		);

		$decoded = json_decode( stripslashes( $content ), true );
		$this->assertSame( 'TestUA', $decoded['user_agent'] );
		$this->assertSame( '127.0.0.1', $decoded['ip_address'] );
		$this->assertSame( 'survey', $decoded['type'] );
		$this->assertSame( array( 'key' => 'val' ), $decoded['post_params'] );
	}

	/** @test */
	public function build_response_data_returns_expected_array_for_survey_and_like(): void {
		$instance = \BeastFeedbacks_Public::get_instance();

		$survey_res = $instance->build_response_data( 123, 'survey' );
		$this->assertSame( 1, $survey_res['success'] );
		$this->assertSame( 1, $survey_res['count'] );
		$this->assertStringContainsString( 'questionnaire', $survey_res['message'] );

		$like_res = $instance->build_response_data( 123, 'like' );
		$this->assertSame( 1, $like_res['success'] );
		$this->assertStringContainsString( 'vote', $like_res['message'] );
	}
}
