<?php

use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BeastFeedbacks_Admin_Test extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		// プラグイン定数が未定義ならダミー定義
		if ( ! defined( 'BEASTFEEDBACKS_DOMAIN' ) ) {
			define( 'BEASTFEEDBACKS_DOMAIN', 'beastfeedbacks' );
		}
		if ( ! defined( 'BEASTFEEDBACKS_URL' ) ) {
			define( 'BEASTFEEDBACKS_URL', 'https://example.com/wp-content/plugins/beastfeedbacks/' );
		}
		if ( ! defined( 'BEASTFEEDBACKS_VERSION' ) ) {
			define( 'BEASTFEEDBACKS_VERSION', '0.1.0-test' );
		}
	}

	/** @var int[] 作成した投稿のIDを記録して後始末 */
	private $created_ids = array();

	protected function tear_down(): void {
		foreach ( array_reverse( $this->created_ids ) as $pid ) {
			if ( get_post( $pid ) ) {
				wp_delete_post( $pid, true );
			}
		}
		$this->created_ids = array();

		unset( $GLOBALS['current_screen'], $GLOBALS['post'] );
		parent::tear_down();
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

	/** @test */
	public function get_instance_returns_singleton(): void {
		$a = \BeastFeedbacks_Admin::get_instance();
		$b = \BeastFeedbacks_Admin::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Admin::class, $a );
		$this->assertSame( $a, $b );
	}

	/** @test */
	public function manage_posts_columns_returns_expected_columns(): void {
		$cols = \BeastFeedbacks_Admin::get_instance()->manage_posts_columns();
		$this->assertSame(
			array(
				'cb',
				'beastfeedbacks_source',
				'beastfeedbacks_type',
				'beastfeedbacks_date',
				'beastfeedbacks_response',
			),
			array_keys( $cols )
		);
	}

	/** @test */
	public function admin_bulk_actions_unsets_edit_only_on_target_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		$in                        = array(
			'edit'  => '編集',
			'trash' => 'ゴミ箱',
		);
		$out                       = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertSame( $in, $out );

		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-beastfeedbacks' );
		$out2                      = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertArrayNotHasKey( 'edit', $out2 );
		$this->assertArrayHasKey( 'trash', $out2 );
	}

	/** @test */
	public function admin_view_tabs_unsets_publish_on_target_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		$views                     = array(
			'all'     => 'All',
			'publish' => 'Published',
		);
		$this->assertSame( $views, \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views ) );

		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-beastfeedbacks' );
		$out                       = \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views );
		$this->assertArrayNotHasKey( 'publish', $out );
		$this->assertArrayHasKey( 'all', $out );
	}

	/** @test */
	public function manage_post_row_actions_unsets_edit_when_beastfeedbacks_and_published(): void {
		$GLOBALS['post'] = (object) array(
			'post_type'   => 'beastfeedbacks',
			'post_status' => 'publish',
		);
		$in              = array(
			'edit'                 => 'Edit',
			'inline hide-if-no-js' => 'Quick Edit',
			'view'                 => 'View',
		);
		$out             = \BeastFeedbacks_Admin::get_instance()->manage_post_row_actions( $in );

		$this->assertArrayNotHasKey( 'edit', $out );
		$this->assertArrayNotHasKey( 'inline hide-if-no-js', $out );
		$this->assertArrayHasKey( 'view', $out );
	}

	/** フェイク WP_Query 相当 */
	private function fakeQuery( array $vars = array() ) {
		return new class( $vars ) {
			public $query_vars = array();
			public function __construct( $vars ) {
				$this->query_vars = $vars; }
			public function get( $k ) {
				return $this->query_vars[ $k ] ?? null; }
			public function set( $k, $v ) {
				$this->query_vars[ $k ] = $v; }
		};
	}

	/** @test */
	public function type_filter_result_sets_meta_query_when_param_present(): void {
		$_GET['beastfeedbacks_type'] = 'survey'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q                           = $this->fakeQuery( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$this->assertArrayHasKey( 'meta_query', $q->query_vars );
		$mq = $q->query_vars['meta_query'];
		$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
		$this->assertSame( 'survey', $mq[0]['value'] );
	}

	/** @test */
	public function type_filter_result_ignores_when_other_post_type(): void {
		$_GET['beastfeedbacks_type'] = 'survey';
		$q                           = $this->fakeQuery( array( 'post_type' => 'post' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_sets_post_parent_when_param_present(): void {
		$_GET['beastfeedbacks_parent_id'] = '55';
		$q                                = $this->fakeQuery(
			array(
				'post_type' => 'beastfeedbacks',
				'fields'    => '',
			)
		);

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertSame( 55, $q->query_vars['post_parent'] );
	}

	/** @test */
	public function source_filter_result_ignores_when_fields_is_id_parent(): void {
		$_GET['beastfeedbacks_parent_id'] = '55';
		$q                                = $this->fakeQuery(
			array(
				'post_type' => 'beastfeedbacks',
				'fields'    => 'id=>parent',
			)
		);

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );
	}

	/** @test */
	public function add_type_filter_has_no_output_on_other_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_type_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_source_filter_has_no_output_on_other_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_source_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_export_button_has_no_output_on_other_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_export_button();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function esc_csv_prefixes_when_dangerous_first_char(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$this->assertSame( "'=SUM(A1:A2)", $admin->esc_csv( '=SUM(A1:A2)' ) );
		$this->assertSame( "'+1+2", $admin->esc_csv( '+1+2' ) );
		$this->assertSame( "'-1", $admin->esc_csv( '-1' ) );
		$this->assertSame( "'@cmd", $admin->esc_csv( '@cmd' ) );

		$this->assertSame( 'safe', $admin->esc_csv( 'safe' ) );
		$this->assertSame( '  space', $admin->esc_csv( '  space' ) ); // 先頭がスペースならそのまま
	}

	/** @test */
	public function manage_posts_custom_column_returns_early_for_unsupported_column(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'unsupported_column', $post_id );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_date_for_beastfeedbacks_date(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_date'   => '2025-01-15 10:20:30',
			)
		);

		$post            = get_post( $post_id );
		$GLOBALS['post'] = $post;

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_date', $post_id );
		$output = ob_get_clean();

		$expected_date = date_i18n( 'Y/m/d', get_the_time( 'U' ) );
		$this->assertSame( $expected_date, $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_type_for_beastfeedbacks_type(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
			)
		);
		add_post_meta( $post_id, 'beastfeedbacks_type', 'survey' );

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_type', $post_id );
		$output = ob_get_clean();

		$this->assertSame( 'survey', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_source_link_for_beastfeedbacks_source(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Parent post
		$parent_id = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'parent post',
			)
		);

		// Child feedback post
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_source', $post_id );
		$output = ob_get_clean();

		$form_url   = get_permalink( $parent_id );
		$parsed_url = wp_parse_url( $form_url );
		$expected   = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $form_url ),
			esc_html( $parsed_url['path'] )
		);

		$this->assertSame( $expected, $output );

		// Invalid / non-existent post ID where $post is null
		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_source', 999999 );
		$no_parent_output = ob_get_clean();

		$this->assertSame( '', $no_parent_output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_response_data_for_vote_and_survey(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Case 1: Non-array or invalid JSON content
		$invalid_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => 'invalid json content',
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $invalid_post_id );
		$invalid_output = ob_get_clean();
		$this->assertSame( '', $invalid_output );

		// Case 2: Vote response
		$vote_content = array(
			'type'        => 'vote',
			'post_params' => array(
				'selected' => 'Option 1',
			),
			'ip_address'  => '192.168.1.1',
			'user_agent'  => 'Test User Agent',
		);
		$vote_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $vote_content ),
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $vote_post_id );
		$vote_output = ob_get_clean();

		$this->assertStringContainsString( 'Option 1', $vote_output );
		$this->assertStringContainsString( 'IP_Address', $vote_output );
		$this->assertStringContainsString( '192.168.1.1', $vote_output );
		$this->assertStringContainsString( 'UserAgent', $vote_output );
		$this->assertStringContainsString( 'Test User Agent', $vote_output );

		// Case 3: Survey response with scalar and array values
		$survey_content = array(
			'type'        => 'survey',
			'post_params' => array(
				'Question 1' => 'Answer 1',
				'Question 2' => array( 'Choice A', 'Choice B' ),
			),
			'ip_address'  => '10.0.0.1',
		);
		$survey_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $survey_content ),
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $survey_post_id );
		$survey_output = ob_get_clean();

		$this->assertStringContainsString( 'Question 1', $survey_output );
		$this->assertStringContainsString( 'Answer 1', $survey_output );
		$this->assertStringContainsString( 'Question 2', $survey_output );
		$this->assertStringContainsString( 'Choice A', $survey_output );
		$this->assertStringContainsString( 'Choice B', $survey_output );
		$this->assertStringContainsString( 'Choice A<br />', $survey_output );
		$this->assertStringContainsString( '10.0.0.1', $survey_output );
		$this->assertStringNotContainsString( 'UserAgent', $survey_output );
	}
}
