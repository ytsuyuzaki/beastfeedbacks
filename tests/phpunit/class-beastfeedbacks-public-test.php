<?php

use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BeastFeedbacks_Public_Test extends TestCase {


	/** @var int[] 作成した投稿のIDを記録して後始末 */
	private $created_ids = array();

	public function set_up(): void {
		parent::set_up();

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
	}

	public function tear_down(): void {
		foreach ( array_reverse( $this->created_ids ) as $pid ) {
			if ( get_post( $pid ) ) {
				wp_delete_post( $pid, true );
			}
		}
		$this->created_ids = array();

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
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'survey parent',
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
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'like parent',
			)
		);

		$existing_like = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
				'post_title'  => 'existing like',
			)
		);
		add_post_meta( $existing_like, 'beastfeedbacks_type', 'like' );
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

	/**
	 * 投稿を作成し、ID を回収・記録するユーティリティ
	 *
	 * @param array $args wp_insert_post() の引数.
	 * @return int 作成した投稿ID
	 */
	private function create_post( array $args ): int {
		$pid                 = wp_insert_post( $args );
		$this->created_ids[] = $pid;
		return $pid;
	}

	/**
	 * AJAX handler に渡すリクエスト値を作る。
	 *
	 * @param array  $params 追加のフォーム入力値.
	 * @param int    $post_id 親投稿 ID.
	 * @param string $type フィードバック種別.
	 * @return array
	 */
	private function create_ajax_request( array $params, int $post_id, string $type ): array {
		return array_merge(
			array(
				'_ajax_nonce'        => wp_create_nonce( 'register_beastfeedbacks_form' ),
				'action'             => 'register_beastfeedbacks_form',
				'id'                 => (string) $post_id,
				'beastfeedbacks_type' => $type,
				'_wp_http_referer'   => '/ignored',
			),
			$params
		);
	}

	/**
	 * AJAX handler を呼び出し、wp_die() の代わりにレスポンスを返す。
	 *
	 * @return array
	 */
	private function call_ajax_handler(): array {
		$die_handler = static function () {
			return static function () {
				throw new RuntimeException( 'wp_die' );
			};
		};

		add_filter( 'wp_die_ajax_handler', $die_handler );
		add_filter( 'wp_die_handler', $die_handler );
		ob_start();
		try {
			\BeastFeedbacks_Public::get_instance()->register_beastfeedbacks_form();
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'wp_die', $e->getMessage() );
		} finally {
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_die_handler', $die_handler );
		}

		return json_decode( ob_get_clean(), true );
	}
}
